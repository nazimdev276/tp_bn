<?php

function lire_liste_texte(?string $texte): array
{
    return array_filter(array_map('trim', explode(',', $texte ?? '')));
}

function ecrire_liste_texte(array $valeurs): string
{
    return implode(', ', $valeurs);
}
