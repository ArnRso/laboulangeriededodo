import { Controller } from '@hotwired/stimulus';

/**
 * Onglets de choix du type de média : n'affiche que le champ correspondant
 * au type sélectionné et tient à jour le champ caché envoyé au serveur.
 */
export default class extends Controller {
    static targets = ['tab', 'panel', 'input'];

    connect() {
        this.select(this.inputTarget.value || this.tabTargets[0].dataset.mediaTypeValue);
    }

    choose(event) {
        event.preventDefault();
        this.select(event.currentTarget.dataset.mediaTypeValue);
    }

    select(value) {
        this.inputTarget.value = value;
        this.dispatch('changed');

        const tab = this.tabTargets.find((candidate) => candidate.dataset.mediaTypeValue === value);
        const accept = tab?.dataset.mediaTypeAccept ?? '';

        for (const candidate of this.tabTargets) {
            const active = candidate === tab;
            candidate.classList.toggle('active', active);
            candidate.setAttribute('aria-selected', String(active));
        }

        // Photo, vidéo et audio partagent le panneau « file » : c'est l'onglet
        // actif qui détermine ce que le sélecteur de fichiers propose.
        const wanted = accept === '' ? value : 'file';

        for (const panel of this.panelTargets) {
            const shown = panel.dataset.mediaTypePanel === wanted;
            panel.classList.toggle('d-none', !shown);

            // Les champs masqués sont désactivés : sinon un contenu saisi puis
            // abandonné dans un autre onglet partirait quand même au serveur.
            for (const field of panel.querySelectorAll('input, textarea')) {
                field.disabled = !shown;

                if (shown && field.type === 'file' && accept !== '') {
                    field.setAttribute('accept', accept);
                }
            }
        }
    }
}
