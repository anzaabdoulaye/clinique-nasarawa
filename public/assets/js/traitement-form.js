function initTraitementForm() {
    const container = document.getElementById('planification-container');
    if (!container) return;

    const dateDebutId = container.dataset.dateDebutId;
    const dateFinId = container.dataset.dateFinId;
    const planificationId = container.dataset.planificationId;

    const dateDebutInput = document.getElementById(dateDebutId);
    const dateFinInput = document.getElementById(dateFinId);
    const planificationHidden = document.getElementById(planificationId);

    if (!dateDebutInput || !dateFinInput || !planificationHidden) return;

    function genererTableau() {
        const debut = dateDebutInput.value;
        const fin = dateFinInput.value;
        if (!debut || !fin) {
            container.innerHTML = '<div class="text-muted">Veuillez renseigner les dates de début et de fin.</div>';
            return;
        }

        const startDate = new Date(debut);
        const endDate = new Date(fin);
        if (startDate > endDate) {
            container.innerHTML = '<div class="text-danger">La date de début doit être antérieure ou égale à la date de fin.</div>';
            return;
        }

        const jours = [];
        let currentDate = new Date(startDate);
        while (currentDate <= endDate) {
            jours.push(new Date(currentDate));
            currentDate.setDate(currentDate.getDate() + 1);
        }

        let planif = {};
        try {
            const hiddenVal = planificationHidden.value;
            if (hiddenVal && hiddenVal !== '{}') {
                planif = JSON.parse(hiddenVal);
            }
        } catch (e) {
            planif = {};
        }

        let html = '<table class="table table-sm table-bordered">';
        html += '<thead><tr><th>Date</th>';
        for (let h = 0; h <= 23; h++) {
            html += `<th class="text-center hour-check">${h}h</th>`;
        }
        html += '</tr></thead><tbody>';

        for (const jour of jours) {
            const dateKey = jour.toISOString().split('T')[0];
            const heuresCochees = planif[dateKey] || [];
            html += `<tr data-date="${dateKey}">`;
            html += `<td><strong>${dateKey}</strong></td>`;
            for (let h = 0; h <= 23; h++) {
                const checked = heuresCochees.includes(h) ? 'checked' : '';
                html += `<td class="text-center"><input type="checkbox" class="hour-checkbox" data-hour="${h}" ${checked}></td>`;
            }
            html += `</tr>`;
        }
        html += '</tbody></table>';
        container.innerHTML = html;

        const checkboxes = container.querySelectorAll('.hour-checkbox');
        checkboxes.forEach(cb => {
            cb.addEventListener('change', () => mettreAJourHidden());
        });
    }

    function mettreAJourHidden() {
        const rows = container.querySelectorAll('tbody tr');
        const planif = {};
        rows.forEach(row => {
            const date = row.dataset.date;
            const heures = [];
            row.querySelectorAll('.hour-checkbox:checked').forEach(cb => {
                heures.push(parseInt(cb.dataset.hour));
            });
            if (heures.length > 0) {
                planif[date] = heures;
            }
        });
        planificationHidden.value = JSON.stringify(planif);
    }

    dateDebutInput.addEventListener('change', genererTableau);
    dateFinInput.addEventListener('change', genererTableau);

    if (dateDebutInput.value && dateFinInput.value) {
        genererTableau();
    }
}