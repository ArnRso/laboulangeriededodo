import { Controller } from '@hotwired/stimulus';

/**
 * Lignes d'une collection de formulaire : on en ajoute depuis le prototype
 * de Symfony, on en retire ; le serveur ne reçoit que ce qui reste à l'écran.
 */
export default class extends Controller {
    static targets = ['items'];
    static values = {
        index: Number,
        prototype: String,
    };

    add() {
        const row = this.prototypeValue.replace(/__name__/g, String(this.indexValue));
        this.indexValue += 1;
        this.itemsTarget.insertAdjacentHTML('beforeend', row);
    }

    remove(event) {
        event.currentTarget.closest('[data-collection-item]').remove();
    }
}
