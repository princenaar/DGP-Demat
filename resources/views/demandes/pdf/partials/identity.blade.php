@php
    use Illuminate\Support\Str;

    $prenom = Str::of($demande->prenom)->lower()->title()->value();
    $nom = Str::upper($demande->nom);
    $categorie = $demande->categorieSocioprofessionnelle?->libelle;
    $trailingPunctuation = $trailingPunctuation ?? '';
    $includeBirthInfo = $includeBirthInfo ?? false;

    $segments = [
        '<span class="nowrap"><strong>M./Mme&nbsp;'.e($prenom).'&nbsp;'.e($nom).'</strong></span>',
    ];

    if ($categorie) {
        $segments[] = e($categorie);
    }

    if ($includeBirthInfo && $demande->date_naissance && $demande->lieu_naissance) {
        $lieuNaissance = Str::of($demande->lieu_naissance)->lower()->title()->value();
        $segments[] = '<span class="nowrap">né(e) le&nbsp;'.e($demande->date_naissance->isoFormat($demande->date_naissance->day == 1 ? 'Do MMMM YYYY' : 'D MMMM YYYY')).'</span> à '.e($lieuNaissance);
    }

    if ($demande->statut === 'contractuel') {
        $segments[] = '<span class="nowrap">contractuel(le)'.e($trailingPunctuation).'</span>';
    } elseif ($demande->statut === 'étatique') {
        $segments[] = 'matricule de solde <strong>n°&nbsp;'.e($demande->matricule).'</strong>'.e($trailingPunctuation);
    } else {
        $segments[count($segments) - 1] .= e($trailingPunctuation);
    }

    $identityHtml = implode(', ', $segments);
@endphp
{!! $identityHtml !!}
