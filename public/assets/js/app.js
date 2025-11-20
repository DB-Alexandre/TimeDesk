/**
 * TimeDesk - JavaScript principal
 */
(function() {
    'use strict';

    // Attendre que le DOM soit chargé
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('entryForm');
        if (!form) return;

        const dateInput = document.getElementById('dateInput');
        const startTimeInput = document.getElementById('startTimeInput');
        const endTimeInput = document.getElementById('endTimeInput');
        const descriptionInput = document.getElementById('descriptionInput');
        const formId = document.getElementById('formId');
        const btnReset = document.getElementById('btnReset');

        /**
         * Récupère la dernière heure de fin pour une date
         */
        async function fetchLastEndTime(dateStr) {
            if (!dateStr) return null;
            
            try {
                const url = `?ajax=lastEnd&date=${encodeURIComponent(dateStr)}`;
                const response = await fetch(url, { cache: 'no-store' });
                
                if (!response.ok) return null;
                
                const data = await response.json();
                return data.lastEnd || null;
            } catch (error) {
                console.error('Erreur lors de la récupération de la dernière heure:', error);
                return null;
            }
        }

        /**
         * Pré-remplit le formulaire pour modification
         */
        window.editEntry = function(entry) {
            dateInput.value = entry.date;
            startTimeInput.value = entry.start_time;
            endTimeInput.value = entry.end_time;
            descriptionInput.value = entry.description || '';
            
            const typeId = entry.type === 'break' ? 'typeBreak' : 'typeWork';
            document.getElementById(typeId).checked = true;
            
            formId.value = entry.id;
            form.action = '?action=update';
            
            form.scrollIntoView({ behavior: 'smooth', block: 'center' });
        };

        /**
         * Réinitialise le formulaire
         */
        async function resetForm() {
            formId.value = '';
            form.action = '?action=create';
            
            const lastEnd = await fetchLastEndTime(dateInput.value);
            startTimeInput.value = lastEnd || '';
            endTimeInput.value = '';
            descriptionInput.value = '';
            document.getElementById('typeWork').checked = true;
        }

        /**
         * Écouter le bouton de réinitialisation
         */
        if (btnReset) {
            btnReset.addEventListener('click', function(e) {
                e.preventDefault();
                resetForm();
            });
        }

        /**
         * Mettre à jour l'heure de début lors du changement de date
         */
        if (dateInput) {
            dateInput.addEventListener('change', async function() {
                // Ne pas modifier en mode édition
                if (formId.value) return;
                
                const lastEnd = await fetchLastEndTime(dateInput.value);
                if (!startTimeInput.value && lastEnd) {
                    startTimeInput.value = lastEnd;
                }
            });
        }

        /**
         * Auto-complétion de l'heure de fin
         * Si l'utilisateur entre une heure de début, suggère +1h pour la fin
         */
        if (startTimeInput && endTimeInput) {
            startTimeInput.addEventListener('change', function() {
                if (!endTimeInput.value) {
                    const start = startTimeInput.value.split(':');
                    if (start.length === 2) {
                        let hours = parseInt(start[0]);
                        const minutes = start[1];
                        hours = (hours + 1) % 24;
                        endTimeInput.value = `${hours.toString().padStart(2, '0')}:${minutes}`;
                    }
                }
            });
        }

        /**
         * Validation du formulaire
         */
        form.addEventListener('submit', function(e) {
            const startTime = startTimeInput.value;
            const endTime = endTimeInput.value;
            
            if (startTime && endTime) {
                const start = startTime.split(':').map(Number);
                const end = endTime.split(':').map(Number);
                const startMinutes = start[0] * 60 + start[1];
                const endMinutes = end[0] * 60 + end[1];
                
                // Vérifier que la fin est après le début (sauf si période sur minuit)
                if (endMinutes < startMinutes && endMinutes + (24 * 60) - startMinutes > 12 * 60) {
                    if (!confirm('La période semble très longue. Continuer ?')) {
                        e.preventDefault();
                        return false;
                    }
                }
            }
        });
    });

    /**
     * Auto-dismiss des alertes après 5 secondes
     */
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
        alerts.forEach(function(alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);

})();
