import { Controller } from '@hotwired/stimulus';

/**
 * Le gain d'aura descend en haut de l'écran, puis s'efface de lui-même.
 */
export default class extends Controller {
    connect() {
        requestAnimationFrame(() => this.element.classList.add('is-visible'));
        this.timer = setTimeout(() => this.element.classList.remove('is-visible'), 4500);
    }

    disconnect() {
        clearTimeout(this.timer);
    }
}
