// js/script.js : JavaScript pur (aucune bibliothèque)

document.addEventListener('DOMContentLoaded', function () {
    var formPersonne = document.getElementById('form-personne');
    if (formPersonne) initPersonne(formPersonne);

    var formNat = document.getElementById('form-nationalite');
    if (formNat) initNationalite(formNat);

    var formTransport = document.getElementById('form-transport');
    if (formTransport) initTransport(formTransport);

    var formSport = document.getElementById('form-sport');
    if (formSport) initSport(formSport);

    var formVoiture = document.getElementById('form-voiture');
    if (formVoiture) initVoiture(formVoiture);

    var formFiliere = document.getElementById('form-filiere');
    if (formFiliere) initFiliere(formFiliere);

    var formEnseignant = document.getElementById('form-enseignant');
    if (formEnseignant) initEnseignant(formEnseignant);

    var formModule = document.getElementById('form-module');
    if (formModule) initModule(formModule);

    var formVoyage = document.getElementById('form-voyage');
    if (formVoyage) initVoyage(formVoyage);

    var formBulletin = document.getElementById('form-bulletin');
    if (formBulletin) initBulletin(formBulletin);

    initMenuDropdown();
});

// ---------- Formulaire Personne ----------
function initPersonne(form) {

    // Validation avant envoi (sauf pour les boutons marqués "formnovalidate")
    form.addEventListener('submit', function (e) {
        if (e.submitter && e.submitter.hasAttribute('formnovalidate')) return;
        var erreurs = validerPersonne(form);
        if (erreurs.length > 0) {
            e.preventDefault();
            alert(erreurs.join('\n'));
        }
    });

    // Bouton "Affichage JavaScript" : affiche les valeurs sans recharger la page
    var btnJs = document.getElementById('btn-affichage-js');
    if (btnJs) {
        btnJs.addEventListener('click', function () {
            afficherValeurs(form);
        });
    }

    // Aperçu de la photo choisie
    var inputPhoto = document.getElementById('photo');
    if (inputPhoto) {
        inputPhoto.addEventListener('change', function () {
            var apercu = document.getElementById('apercu');
            if (this.files.length > 0) {
                apercu.src = URL.createObjectURL(this.files[0]);
                apercu.style.display = 'block';
            } else {
                apercu.style.display = 'none';
            }
        });
    }
}

function validerPersonne(form) {
    var erreurs = [];
    if (!/^[0-9]{1,10}$/.test(form.numero.value.trim())) erreurs.push('Numéro invalide (chiffres uniquement).');
    if (form.nom_prenom.value.trim() === '') erreurs.push('Le nom / prénom est obligatoire.');
    if (form.adresse.value.trim() === '') erreurs.push("L'adresse est obligatoire.");
    if (!/^[0-9]{4,10}$/.test(form.code_postal.value.trim())) erreurs.push('Code postal invalide.');
    if (form.localite.value.trim() === '') erreurs.push('La localité est obligatoire.');
    if (!form.transport || !form.transport.value) erreurs.push('Aucun moyen de transport disponible.');
    if (!form.nationalite) erreurs.push("Aucune nationalité disponible : ajoutez-en une d'abord.");
    return erreurs;
}

// Retourne les valeurs cochées d'un groupe de cases à cocher
function valeursCochees(form, nom) {
    var cases = form.querySelectorAll('input[name="' + nom + '"]:checked');
    var valeurs = [];
    for (var i = 0; i < cases.length; i++) valeurs.push(cases[i].value);
    return valeurs.join(', ');
}

function afficherValeurs(form) {
    var natTexte = '';
    if (form.nationalite && form.nationalite.selectedIndex >= 0) {
        natTexte = form.nationalite.options[form.nationalite.selectedIndex].text;
    }
    var lignes = [
        ['Numéro', form.numero ? form.numero.value : ''],
        ['Civilité', form.civilite ? form.civilite.value : ''],
        ['Nom / Prénom', form.nom_prenom ? form.nom_prenom.value : ''],
        ['Filière', form.filiere ? form.filiere.value : ''],
        ['Adresse', form.adresse ? form.adresse.value : ''],
        ['No postal / Localité', (form.code_postal ? form.code_postal.value : '') + ' ' + (form.localite ? form.localite.value : '')],
        ['Pays', form.pays ? form.pays.value : ''],
        ['Plateforme(s)', valeursCochees(form, 'plateformes[]')],
        ['Application(s)', valeursCochees(form, 'applications[]')],
        ['Moyen de transport', form.transport ? form.transport.value : ''],
        ['Sport', form.sport ? form.sport.value : ''],
        ['Modèle de voiture', form.modele_voiture ? form.modele_voiture.value : ''],
        ['Groupe sanguin', form.groupe_sanguin ? form.groupe_sanguin.value : ''],
        ['Module(s)', valeursCochees(form, 'modules[]')],
        ['Activité(s)', valeursCochees(form, 'activites[]')],
        ['Nationalité', natTexte]
    ];

    var zone = document.getElementById('resultat_js');
    if (!zone) return;
    zone.textContent = '';                       // on vide la zone
    var titre = document.createElement('strong');
    titre.textContent = 'Affichage JavaScript';
    zone.appendChild(titre);

    for (var i = 0; i < lignes.length; i++) {
        var p = document.createElement('div');
        p.textContent = lignes[i][0] + ' : ' + lignes[i][1];   // textContent = pas de risque XSS
        zone.appendChild(p);
    }
    zone.style.display = 'block';
}

// ---------- Formulaire Nationalité ----------
function initNationalite(form) {
    form.addEventListener('submit', function (e) {
        if (e.submitter && e.submitter.hasAttribute('formnovalidate')) return;
        var code = form.code.value.trim();
        var libelle = form.libelle.value.trim();
        if (code === '' || code.length > 3 || libelle === '' || libelle.length > 40) {
            e.preventDefault();
            alert('Code : 1 à 3 caractères. Nationalité : 1 à 40 caractères.');
        }
    });
}

function initTransport(form) {
    form.addEventListener('submit', function (e) {
        if (e.submitter && e.submitter.hasAttribute('formnovalidate')) return;
        var libelle = form.libelle.value.trim();
        if (libelle === '' || libelle.length > 20) {
            e.preventDefault();
            alert('Le moyen de transport est obligatoire (20 caractères maximum).');
        }
    });
}

function initSport(form) {
    form.addEventListener('submit', function (e) {
        if (e.submitter && e.submitter.hasAttribute('formnovalidate')) return;
        var libelle = form.libelle.value.trim();
        if (libelle === '' || libelle.length > 40) {
            e.preventDefault();
            alert('Le sport est obligatoire (40 caractères maximum).');
        }
    });
}

function initVoiture(form) {
    form.addEventListener('submit', function (e) {
        if (e.submitter && e.submitter.hasAttribute('formnovalidate')) return;
        var libelle = form.libelle.value.trim();
        if (libelle === '' || libelle.length > 50) {
            e.preventDefault();
            alert('Le modèle de voiture est obligatoire (50 caractères maximum).');
        }
    });
}

function initFiliere(form) {
    form.addEventListener('submit', function (e) {
        if (e.submitter && e.submitter.hasAttribute('formnovalidate')) return;
        var nom = form.nom ? form.nom.value.trim() : '';
        var action = e.submitter ? e.submitter.value : '';
        if (action === 'modifier') {
            var id = form.id ? form.id.value.trim() : '';
            if (id === '') {
                e.preventDefault();
                alert('Veuillez préciser l\'ID de la filière à modifier.');
                return;
            }
        }
        if (nom === '' || nom.length > 50) {
            e.preventDefault();
            alert('Le nom de la filière est obligatoire (50 caractères maximum).');
        }
    });
}

function initEnseignant(form) {
    // Initialisation du dropdown à choix multiples pour les modules
    initModulesMultiselect(form);

    form.addEventListener('submit', function (e) {
        if (e.submitter && e.submitter.hasAttribute('formnovalidate')) return;
        var action = e.submitter ? e.submitter.value : '';
        if (action === 'modifier') {
            var id = form.id ? form.id.value.trim() : '';
            if (id === '') {
                e.preventDefault();
                alert('Veuillez préciser l\'ID de l\'enseignant à modifier.');
                return;
            }
        }
        var erreurs = [];
        if (!/^[0-9]{1,10}$/.test(form.numero.value.trim())) erreurs.push('Numéro invalide (chiffres uniquement, 1 à 10 chiffres).');
        if (form.nom_prenom.value.trim() === '') erreurs.push('Le nom / prénom est obligatoire.');
        if (form.filiere && form.filiere.value.trim() === '') erreurs.push('La filière est obligatoire.');
        if (form.adresse.value.trim() === '') erreurs.push('L\'adresse est obligatoire.');
        if (form.date_naissance.value.trim() === '') erreurs.push('La date de naissance est obligatoire.');
        if (form.lieu_naissance.value.trim() === '') erreurs.push('Le lieu de naissance est obligatoire.');
        if (form.specialite.value.trim() === '') erreurs.push('La spécialité est obligatoire.');

        if (erreurs.length > 0) {
            e.preventDefault();
            alert(erreurs.join('\n'));
        }
    });
}

// Gestion interactive du dropdown à choix multiples des modules
function initModulesMultiselect(form) {
    var dropdown = document.getElementById('modules-dropdown');
    if (!dropdown) return;

    var trigger     = document.getElementById('modules-trigger');
    var menu        = document.getElementById('modules-menu');
    var tagsBox     = document.getElementById('modules-tags');
    var placeholder = document.getElementById('modules-placeholder');
    var searchInput = document.getElementById('modules-filter');
    var btnAll      = document.getElementById('btn-select-all-modules');
    var btnClear    = document.getElementById('btn-clear-modules');
    var btnFiliere  = document.getElementById('btn-filter-filiere');
    var selectFiliere = document.getElementById('filiere');

    var items = dropdown.querySelectorAll('.multiselect-item');

    function updateTags() {
        if (!tagsBox) return;
        tagsBox.innerHTML = '';
        var checkedBoxes = dropdown.querySelectorAll('input[name="modules[]"]:checked');

        if (checkedBoxes.length > 0) {
            if (placeholder) placeholder.style.display = 'none';
            checkedBoxes.forEach(function (cb) {
                var code = cb.value;
                var item = cb.closest('.multiselect-item');
                var tag = document.createElement('span');
                tag.className = 'multiselect-tag';
                tag.innerHTML = '<strong>' + code + '</strong>' +
                    '<span class="multiselect-tag-remove" data-val="' + code + '" title="Retirer">&times;</span>';

                tag.querySelector('.multiselect-tag-remove').addEventListener('click', function (ev) {
                    ev.stopPropagation();
                    cb.checked = false;
                    if (item) item.classList.remove('selected');
                    updateTags();
                });

                tagsBox.appendChild(tag);
                if (item) item.classList.add('selected');
            });
        } else {
            if (placeholder) placeholder.style.display = 'inline';
            items.forEach(function (it) {
                it.classList.remove('selected');
            });
        }
    }

    // Bascule de l'ouverture du menu
    trigger.addEventListener('click', function (e) {
        var isOpen = menu.classList.contains('open');
        if (isOpen) {
            menu.classList.remove('open');
            trigger.setAttribute('aria-expanded', 'false');
        } else {
            menu.classList.add('open');
            trigger.setAttribute('aria-expanded', 'true');
            if (searchInput) searchInput.focus();
        }
    });

    // Clic en dehors pour fermer
    document.addEventListener('click', function (e) {
        if (!dropdown.contains(e.target)) {
            menu.classList.remove('open');
            trigger.setAttribute('aria-expanded', 'false');
        }
    });

    // Changement de case à cocher
    items.forEach(function (it) {
        var cb = it.querySelector('input[type="checkbox"]');
        if (cb) {
            cb.addEventListener('change', function () {
                updateTags();
            });
        }
    });

    // Recherche / filtrage en direct
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            var q = this.value.toLowerCase().trim();
            items.forEach(function (it) {
                var code = (it.getAttribute('data-code') || '').toLowerCase();
                var desig = (it.getAttribute('data-desig') || '').toLowerCase();
                var fil = (it.getAttribute('data-filiere') || '').toLowerCase();
                if (q === '' || code.indexOf(q) !== -1 || desig.indexOf(q) !== -1 || fil.indexOf(q) !== -1) {
                    it.style.display = 'flex';
                } else {
                    it.style.display = 'none';
                }
            });
        });
    }

    // Boutons Tout cocher / Tout décocher
    if (btnAll) {
        btnAll.addEventListener('click', function () {
            items.forEach(function (it) {
                if (it.style.display !== 'none') {
                    var cb = it.querySelector('input[type="checkbox"]');
                    if (cb) cb.checked = true;
                }
            });
            updateTags();
        });
    }

    if (btnClear) {
        btnClear.addEventListener('click', function () {
            items.forEach(function (it) {
                var cb = it.querySelector('input[type="checkbox"]');
                if (cb) cb.checked = false;
            });
            updateTags();
        });
    }

    // Réactivité avec le champ Filière
    function updateFiliereSuggestion() {
        if (!selectFiliere || !btnFiliere) return;
        var fVal = selectFiliere.value.trim();
        if (fVal !== '') {
            btnFiliere.style.display = 'inline-block';
            btnFiliere.textContent = 'Cocher modules ' + fVal;
            // Mettre en valeur visuelle les modules de cette filière
            items.forEach(function (it) {
                var itFil = it.getAttribute('data-filiere');
                if (itFil === fVal) {
                    it.classList.add('match-filiere');
                } else {
                    it.classList.remove('match-filiere');
                }
            });
        } else {
            btnFiliere.style.display = 'none';
            items.forEach(function (it) { it.classList.remove('match-filiere'); });
        }
    }

    if (selectFiliere) {
        selectFiliere.addEventListener('change', updateFiliereSuggestion);
        updateFiliereSuggestion();
    }

    if (btnFiliere) {
        btnFiliere.addEventListener('click', function () {
            var fVal = selectFiliere ? selectFiliere.value.trim() : '';
            if (!fVal) return;
            items.forEach(function (it) {
                if (it.getAttribute('data-filiere') === fVal) {
                    var cb = it.querySelector('input[type="checkbox"]');
                    if (cb) cb.checked = true;
                }
            });
            updateTags();
        });
    }

    // Initialiser les tags au chargement
    updateTags();
}

function initModule(form) {
    form.addEventListener('submit', function (e) {
        if (e.submitter && e.submitter.hasAttribute('formnovalidate')) return;
        var action = e.submitter ? e.submitter.value : '';
        if (action === 'modifier') {
            var id = form.id ? form.id.value.trim() : '';
            if (id === '') {
                e.preventDefault();
                alert('Veuillez préciser l\'ID du module à modifier.');
                return;
            }
        }
        var erreurs = [];
        if (form.code_module.value.trim() === '') erreurs.push('Le code module est obligatoire.');
        if (form.designation.value.trim() === '') erreurs.push('La désignation est obligatoire.');
        var coeff = parseInt(form.coefficient.value, 10);
        if (isNaN(coeff) || coeff <= 0) erreurs.push('Le coefficient doit être un entier positif.');
        var volume = parseInt(form.volume_horaire.value, 10);
        if (isNaN(volume) || volume <= 0) erreurs.push('Le volume horaire doit être un entier positif.');
        if (!form.filiere.value) erreurs.push('Veuillez sélectionner une filière.');

        if (erreurs.length > 0) {
            e.preventDefault();
            alert(erreurs.join('\n'));
        }
    });
}

function initMenuDropdown() {
    var btn = document.getElementById('btn-tables-menu');
    var submenu = document.getElementById('submenu-tables');
    if (!btn || !submenu) return;

    btn.addEventListener('click', function (e) {
        // Toggle the submenu
        e.preventDefault();
        submenu.classList.toggle('show');
    });

    document.addEventListener('click', function (e) {
        if (!btn.contains(e.target) && !submenu.contains(e.target)) {
            submenu.classList.remove('show');
        }
    });
}

// ---------- Formulaire Voyage ----------
function initVoyage(form) {
    // Validation avant envoi
    form.addEventListener('submit', function (e) {
        if (e.submitter && e.submitter.hasAttribute('formnovalidate')) return;
        var erreurs = validerVoyage(form);
        if (erreurs.length > 0) {
            e.preventDefault();
            alert(erreurs.join('\n'));
        }
    });

    // Bouton "Affichage JavaScript"
    var btnJsVoyage = document.getElementById('btn-affichage-js-voyage');
    if (btnJsVoyage) {
        btnJsVoyage.addEventListener('click', function () {
            afficherValeursVoyage(form);
        });
    }

    // Basculer l'affichage des informations complémentaires masquées
    var btnToggle = document.getElementById('btn-toggle-etudiant');
    var zoneCachee = document.getElementById('champs-caches-etudiant');
    if (btnToggle && zoneCachee) {
        btnToggle.addEventListener('click', function () {
            if (zoneCachee.style.display === 'none' || zoneCachee.style.display === '') {
                zoneCachee.style.display = 'table-row-group';
                btnToggle.textContent = '👁️ Masquer les autres détails étudiants masqués';
            } else {
                zoneCachee.style.display = 'none';
                btnToggle.textContent = '👁️ Afficher / Masquer les autres détails étudiants masqués';
            }
        });
    }
}

function validerVoyage(form) {
    var erreurs = [];
    if (!/^[0-9]{1,10}$/.test(form.numero.value.trim())) erreurs.push('Numéro invalide (chiffres uniquement).');
    if (form.nom_prenom.value.trim() === '') erreurs.push('Le nom / prénom est obligatoire.');
    if (form.adresse.value.trim() === '') erreurs.push("L'adresse est obligatoire.");
    if (form.code_postal && form.code_postal.value.trim() !== '' && !/^[0-9]{4,10}$/.test(form.code_postal.value.trim())) {
        erreurs.push('Code postal invalide (4 à 10 chiffres).');
    }
    return erreurs;
}

function afficherValeursVoyage(form) {
    var natTexte = '';
    if (form.nationalite && form.nationalite.selectedIndex >= 0) {
        natTexte = form.nationalite.options[form.nationalite.selectedIndex].text;
    }

    var travelWithVal = '';
    var travelWithRadio = form.querySelector('input[name="travel_with"]:checked');
    if (travelWithRadio) {
        travelWithVal = travelWithRadio.value;
    }

    var lignes = [
        ['Numéro', form.numero ? form.numero.value : ''],
        ['Civilité', form.civilite ? form.civilite.value : ''],
        ['Nom / Prénom', form.nom_prenom ? form.nom_prenom.value : ''],
        ['Filière', form.filiere ? form.filiere.value : ''],
        ['Adresse', form.adresse ? form.adresse.value : ''],
        ['Pays', form.pays ? form.pays.value : ''],
        ['Méthodes de voyage (Travel methodes)', valeursCochees(form, 'travel_methodes[]')],
        ['Accompagnement (Travel with)', travelWithVal],
        ['Description', form.description ? form.description.value : ''],
        ['No postal / Localité', ((form.code_postal ? form.code_postal.value : '') + ' ' + (form.localite ? form.localite.value : '')).trim()],
        ['Plateforme(s)', valeursCochees(form, 'plateformes[]')],
        ['Application(s)', valeursCochees(form, 'applications[]')],
        ['Transport habituel', form.transport ? form.transport.value : ''],
        ['Sport', form.sport ? form.sport.value : ''],
        ['Modèle de voiture', valeursCochees(form, 'modele_voiture[]')],
        ['Groupe sanguin', form.groupe_sanguin ? form.groupe_sanguin.value : ''],
        ['Module(s)', valeursCochees(form, 'modules[]')],
        ['Activité(s)', valeursCochees(form, 'activites[]')],
        ['Nationalité', natTexte]
    ];

    var zone = document.getElementById('resultat_js_voyage');
    if (!zone) return;
    zone.textContent = '';
    var titre = document.createElement('h3');
    titre.textContent = 'Affichage JavaScript : Données Voyage & Étudiant';
    titre.style.marginTop = '0';
    titre.style.color = '#4f46e5';
    zone.appendChild(titre);

    for (var i = 0; i < lignes.length; i++) {
        var p = document.createElement('div');
        p.style.padding = '3px 0';
        var strong = document.createElement('strong');
        strong.textContent = lignes[i][0] + ' : ';
        p.appendChild(strong);
        p.appendChild(document.createTextNode(lignes[i][1] || '—'));
        zone.appendChild(p);
    }
    zone.style.display = 'block';
}

// ---------- Formulaire Bulletin de Notes ----------
function initBulletin(form) {
    var selectModule = document.getElementById('module_id');
    var inputCode    = document.getElementById('code_module');
    var inputCoeff   = document.getElementById('coefficient');
    var inputNote    = document.getElementById('note');
    var inputNoteId  = document.getElementById('note_id');
    var selectNationalite = document.getElementById('nationalite_code');
    var badgeNote    = document.getElementById('note-existante-badge');

    function majModule() {
        if (!selectModule) return;
        var opt = selectModule.options[selectModule.selectedIndex];
        if (opt && opt.value) {
            if (inputCode) inputCode.value = opt.getAttribute('data-code') || '';
            if (inputCoeff) inputCoeff.value = opt.getAttribute('data-coeff') || '';

            // Si des notes de l'étudiant sont connues en mémoire
            if (window.NOTES_ETUDIANT && typeof window.NOTES_ETUDIANT === 'object') {
                var moduleId = opt.value;
                if (window.NOTES_ETUDIANT[moduleId]) {
                    var noteData = window.NOTES_ETUDIANT[moduleId];
                    if (inputNote) inputNote.value = noteData.note;
                    if (inputNoteId) inputNoteId.value = noteData.note_id;
                    if (selectNationalite) selectNationalite.value = noteData.nationalite_code || '';
                    if (!badgeNote) {
                        badgeNote = document.createElement('span');
                        badgeNote.id = 'note-existante-badge';
                        badgeNote.style.cssText = 'display:inline-block; margin-left: 10px; background: #e0e7ff; color: #4338ca; padding: 2px 8px; border-radius: 4px; font-size: 12px; font-weight: 600;';
                        badgeNote.textContent = 'Note déjà enregistrée';
                        if (inputNote && inputNote.parentNode) {
                            inputNote.parentNode.appendChild(badgeNote);
                        }
                    } else {
                        badgeNote.style.display = 'inline-block';
                    }
                } else {
                    if (inputNoteId) inputNoteId.value = '';
                    if (selectNationalite) selectNationalite.value = '';
                    if (badgeNote) badgeNote.style.display = 'none';
                }
            }
        } else {
            if (inputCode) inputCode.value = '';
            if (inputCoeff) inputCoeff.value = '';
            if (inputNoteId) inputNoteId.value = '';
            if (selectNationalite) selectNationalite.value = '';
            if (badgeNote) badgeNote.style.display = 'none';
        }
    }

    if (selectModule) {
        selectModule.addEventListener('change', majModule);
    }

    // Validation avant soumission
    form.addEventListener('submit', function (e) {
        if (e.submitter && e.submitter.hasAttribute('formnovalidate')) return;
        var erreurs = validerBulletin(form);
        if (erreurs.length > 0) {
            e.preventDefault();
            alert(erreurs.join('\n'));
        }
    });

    var btnReset = document.getElementById('btn-reset-bulletin');
    if (btnReset) {
        btnReset.addEventListener('click', function () {
            setTimeout(majModule, 50);
        });
    }
}

function validerBulletin(form) {
    var erreurs = [];
    var etudiantId = form.etudiant_id ? form.etudiant_id.value.trim() : '';
    var numero     = form.numero ? form.numero.value.trim() : '';
    if (etudiantId === '' || numero === '') {
        erreurs.push("Veuillez d'abord rechercher et sélectionner un étudiant.");
    }
    if (!form.module_id || form.module_id.value.trim() === '') {
        erreurs.push("Veuillez sélectionner un module d'enseignement.");
    }
    if (!form.nationalite_code || form.nationalite_code.value.trim() === '') {
        erreurs.push("Veuillez sélectionner une nationalité.");
    }
    var noteVal = form.note ? form.note.value.trim().replace(',', '.') : '';
    if (noteVal === '') {
        erreurs.push("La note est obligatoire.");
    } else {
        var num = parseFloat(noteVal);
        if (isNaN(num) || num < 0 || num > 20) {
            erreurs.push("La note doit être un nombre compris entre 0 et 20.");
        }
    }
    return erreurs;
}

