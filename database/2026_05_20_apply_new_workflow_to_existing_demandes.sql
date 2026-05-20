-- Apply the new automatic workflow rules to existing demandes.
--
-- Rule 1:
--   If the demande type has at least one active default agent, existing demandes
--   still in a pre-agent state are moved directly to VALIDEE and remain
--   unassigned (agent_id = NULL) so they stay in the shared agent queue.
--
-- Rule 2:
--   For types without active default agents, any workflow_transitions row marked
--   automatique = 1 is applied generically and chained until no automatic
--   transition remains available.
--
-- Run on MySQL from the project database, for example:
--   mysql -u <user> -p <database> < database/2026_05_20_apply_new_workflow_to_existing_demandes.sql

DROP PROCEDURE IF EXISTS apply_existing_automatic_workflow;

DELIMITER //

CREATE PROCEDURE apply_existing_automatic_workflow()
BEGIN
    DECLARE step_count INT DEFAULT 0;
    DECLARE inserted_count INT DEFAULT 1;

    WHILE inserted_count > 0 AND step_count < 32 DO
        SET step_count = step_count + 1;

        DELETE FROM workflow_existing_changes;

        INSERT INTO workflow_existing_changes (demande_id, old_etat_id, new_etat_id, commentaire)
        SELECT
            ranked.demande_id,
            ranked.old_etat_id,
            ranked.new_etat_id,
            ranked.commentaire
        FROM (
            SELECT
                d.id AS demande_id,
                d.etat_demande_id AS old_etat_id,
                wt.etat_cible_id AS new_etat_id,
                CONCAT('Transition automatique : ', source.nom, ' -> ', cible.nom, '.') AS commentaire,
                ROW_NUMBER() OVER (
                    PARTITION BY d.id
                    ORDER BY wt.ordre ASC, wt.id ASC
                ) AS transition_rank
            FROM demandes d
            INNER JOIN type_documents td ON td.id = d.type_document_id
            INNER JOIN workflow_transitions wt
                ON wt.type_document_id = d.type_document_id
               AND wt.etat_source_id = d.etat_demande_id
               AND wt.automatique = 1
            INNER JOIN etat_demandes source ON source.id = wt.etat_source_id
            INNER JOIN etat_demandes cible ON cible.id = wt.etat_cible_id
            WHERE wt.etat_cible_id <> d.etat_demande_id
              AND NOT EXISTS (
                  SELECT 1
                  FROM type_document_default_agents tdda
                  INNER JOIN users u ON u.id = tdda.user_id
                  WHERE tdda.type_document_id = d.type_document_id
                    AND u.is_active = 1
              )
              AND NOT (
                  cible.nom = 'VALIDEE'
                  AND (
                      (
                          td.eligibilite IS NOT NULL
                          AND td.eligibilite <> ''
                          AND d.statut <> td.eligibilite
                      )
                      OR (
                          JSON_UNQUOTE(JSON_EXTRACT(td.champs_requis, '$.categorie_socioprofessionnelle_id')) IN ('true', '1')
                          AND d.categorie_socioprofessionnelle_id IS NULL
                      )
                      OR (
                          JSON_UNQUOTE(JSON_EXTRACT(td.champs_requis, '$.date_prise_service')) IN ('true', '1')
                          AND d.date_prise_service IS NULL
                      )
                      OR (
                          JSON_UNQUOTE(JSON_EXTRACT(td.champs_requis, '$.date_fin_service')) IN ('true', '1')
                          AND d.date_fin_service IS NULL
                      )
                      OR (
                          JSON_UNQUOTE(JSON_EXTRACT(td.champs_requis, '$.date_depart_retraite')) IN ('true', '1')
                          AND d.date_depart_retraite IS NULL
                      )
                      OR (
                          EXISTS (
                              SELECT 1
                              FROM pieces_requises pr
                              WHERE pr.type_document_id = d.type_document_id
                                AND pr.obligatoire = 1
                          )
                          AND NOT EXISTS (
                              SELECT 1
                              FROM fichier_justificatifs fj
                              WHERE fj.demande_id = d.id
                          )
                      )
                      OR (
                          d.date_fin_service IS NOT NULL
                          AND d.date_prise_service IS NOT NULL
                          AND d.date_fin_service <= d.date_prise_service
                      )
                  )
              )
        ) ranked
        WHERE ranked.transition_rank = 1;

        SET inserted_count = ROW_COUNT();

        IF inserted_count > 0 THEN
            INSERT INTO historique_etats (demande_id, etat_demande_id, user_id, commentaire, created_at, updated_at)
            SELECT
                demande_id,
                new_etat_id,
                NULL,
                commentaire,
                NOW(),
                NOW()
            FROM workflow_existing_changes;

            UPDATE demandes d
            INNER JOIN workflow_existing_changes c ON c.demande_id = d.id
            SET
                d.etat_demande_id = c.new_etat_id,
                d.commentaire = TRIM(CONCAT_WS('\n', NULLIF(d.commentaire, ''), CONCAT(DATE_FORMAT(NOW(), '%d/%m/%Y %H:%i'), ' - Système : ', c.commentaire))),
                d.updated_at = NOW();
        END IF;
    END WHILE;

    IF inserted_count > 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Boucle possible dans les transitions automatiques : limite de 32 étapes atteinte.';
    END IF;
END//

DELIMITER ;

START TRANSACTION;

SET @etat_en_attente_id := (SELECT id FROM etat_demandes WHERE nom = 'EN ATTENTE' LIMIT 1);
SET @etat_receptionnee_id := (SELECT id FROM etat_demandes WHERE nom = 'RECEPTIONNEE' LIMIT 1);
SET @etat_validee_id := (SELECT id FROM etat_demandes WHERE nom = 'VALIDEE' LIMIT 1);

DROP TEMPORARY TABLE IF EXISTS workflow_existing_changes;

CREATE TEMPORARY TABLE workflow_existing_changes (
    demande_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
    old_etat_id BIGINT UNSIGNED NOT NULL,
    new_etat_id BIGINT UNSIGNED NOT NULL,
    commentaire TEXT NOT NULL
);

-- Priority rule: active default agent(s) => direct validation from previous states.
INSERT INTO workflow_existing_changes (demande_id, old_etat_id, new_etat_id, commentaire)
SELECT
    d.id,
    d.etat_demande_id,
    @etat_validee_id,
    'Validation automatique : agent(s) par défaut configuré(s).'
FROM demandes d
INNER JOIN etat_demandes ed ON ed.id = d.etat_demande_id
WHERE ed.nom NOT IN ('VALIDEE', 'REFUSEE', 'DEMANDE DE COMPLEMENTS', 'EN SIGNATURE', 'SIGNEE', 'SUSPENDUE')
  AND EXISTS (
      SELECT 1
      FROM type_document_default_agents tdda
      INNER JOIN users u ON u.id = tdda.user_id
      WHERE tdda.type_document_id = d.type_document_id
        AND u.is_active = 1
  );

INSERT INTO historique_etats (demande_id, etat_demande_id, user_id, commentaire, created_at, updated_at)
SELECT
    demande_id,
    new_etat_id,
    NULL,
    commentaire,
    NOW(),
    NOW()
FROM workflow_existing_changes;

UPDATE demandes d
INNER JOIN workflow_existing_changes c ON c.demande_id = d.id
SET
    d.etat_demande_id = c.new_etat_id,
    d.agent_id = NULL,
    d.commentaire = TRIM(CONCAT_WS('\n', NULLIF(d.commentaire, ''), CONCAT(DATE_FORMAT(NOW(), '%d/%m/%Y %H:%i'), ' - Système : ', c.commentaire))),
    d.updated_at = NOW();

CALL apply_existing_automatic_workflow();

DROP TEMPORARY TABLE IF EXISTS workflow_existing_changes;

COMMIT;

DROP PROCEDURE IF EXISTS apply_existing_automatic_workflow;

SELECT
    d.id,
    d.numero_demande,
    td.code AS type_document,
    ed.nom AS etat_final,
    d.agent_id,
    d.updated_at
FROM demandes d
INNER JOIN type_documents td ON td.id = d.type_document_id
INNER JOIN etat_demandes ed ON ed.id = d.etat_demande_id
ORDER BY d.updated_at DESC, d.id DESC;
