document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ Script laboratoire.js chargé et prêt.');

    // -------------------------------------------------------------------------
    // 1 & 2. GESTION DES CLICS (Ajout / Suppression) via Délégation d'événements
    // -------------------------------------------------------------------------
    document.addEventListener('click', function(e) {
        
        // --- 1. AJOUT D'UN ANTIBIOGRAMME ---
        const btnAddAntibiogramme = e.target.closest('.js-add-antibiogramme');
        if (btnAddAntibiogramme) {
            e.preventDefault(); // Empêche la page de remonter
            console.log('👉 Clic sur "Ajouter un germe identifié" détecté');

            const wrapperAntibiogrammes = document.getElementById('antibiogrammes-wrapper');
            if (!wrapperAntibiogrammes) {
                console.error("❌ ERREUR : Le conteneur #antibiogrammes-wrapper est introuvable.");
                return;
            }

            let index = wrapperAntibiogrammes.dataset.index ? parseInt(wrapperAntibiogrammes.dataset.index) : 0;
            let prototype = wrapperAntibiogrammes.dataset.prototype;

            if (!prototype) {
                console.error("❌ ERREUR : L'attribut data-prototype est manquant sur le wrapper.");
                return;
            }

            // Remplacement intelligent pour ne pas casser les sous-formulaires
            let newForm = prototype.replace(/antibiogrammes___name__/g, `antibiogrammes_${index}`)
                                   .replace(/\[antibiogrammes\]\[__name__\]/g, `[antibiogrammes][${index}]`);
            
            // Fallback universel au cas où le nom du formulaire serait différent
            if (newForm === prototype) {
                newForm = prototype.replace(/__name__/g, index);
            }

            let tempDiv = document.createElement('div');
            tempDiv.innerHTML = newForm;
            wrapperAntibiogrammes.appendChild(tempDiv.firstElementChild);
            
            wrapperAntibiogrammes.dataset.index = index + 1;
            console.log(`✅ Nouvel antibiogramme ajouté (Index: ${index})`);
        }

        // --- 2. SUPPRESSION D'UN ANTIBIOGRAMME ---
        const btnRemoveAntibiogramme = e.target.closest('.js-remove-antibiogramme');
        if (btnRemoveAntibiogramme) {
            e.preventDefault();
            const item = btnRemoveAntibiogramme.closest('.antibiogramme-item');
            if (item) {
                item.remove();
                console.log('🗑️ Antibiogramme supprimé');
            }
        }

        // --- 3. SUPPRESSION D'UNE LIGNE STANDARD (Macroscopie, etc.) ---
        const btnRemoveLine = e.target.closest('.js-remove-result-line');
        if (btnRemoveLine) {
            e.preventDefault();
            const lineItem = btnRemoveLine.closest('.js-result-line-item');
            if (lineItem) {
                lineItem.remove();
            }
        }
    });

    // -------------------------------------------------------------------------
    // 3. CHARGEMENT DYNAMIQUE DES ANTIBIOTIQUES (Appel API lors de la sélection)
    // -------------------------------------------------------------------------
    document.addEventListener('change', function(e) {
        if (e.target && e.target.classList.contains('js-germe-select')) {
            const germeId = e.target.value;
            console.log(`🦠 Changement de germe détecté (ID: ${germeId || 'Aucun'})`);
            
            const wrapper = e.target.closest('.antibiogramme-item').querySelector('.js-lignes-wrapper');
            
            if (!wrapper) {
                console.error("❌ ERREUR : Conteneur de la grille (.js-lignes-wrapper) introuvable.");
                return;
            }

            if (!germeId) {
                // L'utilisateur a remis le menu déroulant sur le choix vide
                wrapper.innerHTML = '<div class="text-muted small fst-italic text-center js-empty-msg">Sélectionnez un germe pour générer automatiquement la liste des antibiotiques.</div>';
                return;
            }

            // Affichage du loader
            wrapper.innerHTML = '<div class="text-center p-3"><div class="spinner-border spinner-border-sm text-primary" role="status"></div><span class="ms-2">Chargement de la grille d\'antibiotiques...</span></div>';

            // Appel AJAX
            fetch('/api/laboratoire/antibiotiques/actifs')
                .then(response => {
                    if (!response.ok) throw new Error("Erreur réseau HTTP : " + response.status);
                    return response.json();
                })
                .then(data => {
                    console.log(`💊 ${data.length} antibiotiques récupérés depuis l'API.`);
                    wrapper.innerHTML = ''; 
                    
                    let index = wrapper.dataset.index ? parseInt(wrapper.dataset.index) : 0;
                    const prototype = wrapper.dataset.prototype;

                    if (!prototype) {
                        console.error("❌ ERREUR : Attribut data-prototype manquant sur les lignes.");
                        return;
                    }

                    if (data.length === 0) {
                        wrapper.innerHTML = '<div class="alert alert-warning py-2 mb-0">Aucun antibiotique actif configuré dans le système. Allez dans les paramètres pour en ajouter.</div>';
                        return;
                    }

                    data.forEach(antibiotique => {
                        let newForm = prototype.replace(/__name__/g, index);
                        let tempDiv = document.createElement('div');
                        tempDiv.innerHTML = newForm;

                        // Sélection de l'antibiotique dans le menu déroulant caché/désactivé
                        let atbSelect = tempDiv.querySelector('select[id$="_antibiotique"]');
                        if (atbSelect) {
                            atbSelect.value = antibiotique.id;
                            atbSelect.style.pointerEvents = 'none'; // Empêche le clic
                            atbSelect.classList.add('bg-light', 'text-dark', 'fw-bold', 'border-0');
                        }

                        wrapper.appendChild(tempDiv.firstElementChild);
                        index++;
                    });

                    wrapper.dataset.index = index;
                    console.log('✅ Grille d\'antibiogramme générée avec succès.');
                })
                .catch(error => {
                    console.error('❌ Erreur API:', error);
                    wrapper.innerHTML = '<div class="alert alert-danger py-2 mb-0">Erreur de connexion à la base de données. Veuillez réessayer.</div>';
                });
        }
    });
});