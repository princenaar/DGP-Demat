@php
    use Illuminate\Support\Str;

    $prenom = Str::of($demande->prenom)->lower()->title()->value();
    $nom = Str::upper($demande->nom);
    $categorie = $demande->categorieSocioprofessionnelle?->libelle;
    $trailingPunctuation = $trailingPunctuation ?? '';

    $segments = [
        '<span class="nowrap"><strong>M./Mme&nbsp;'.e($prenom).'&nbsp;'.e($nom).'</strong></span>',
    ];

    if ($categorie) {
        $segments[] = e($categorie);
    }

    if ($demande->statut === 'contractuel') {
        $segments[] = '<span class="nowrap">contractuel(le)'.e($trailingPunctuation).'</span>';
    } elseif ($demande->statut === 'étatique') {
        $segments[] = '<span class="nowrap">matricule de solde n°&nbsp;<strong>'.e($demande->matricule).'</strong>'.e($trailingPunctuation).'</span>';
    } else {
        $segments[count($segments) - 1] .= e($trailingPunctuation);
    }

    $identityHtml = implode(', ', $segments);
@endphp
{!! $identityHtml !!}
