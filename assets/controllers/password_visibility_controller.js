import { Controller } from '@hotwired/stimulus';

/**
 * Bascule un champ mot de passe entre masqué et lisible.
 *
 * L'icône est soit une icône Bootstrap (classes bi-eye / bi-eye-slash), soit
 * un texte porté par data-show / data-hide : le contrôleur gère les deux.
 */
export default class extends Controller {
    static targets = ['input', 'icon', 'button'];

    toggle() {
        const revealed = this.inputTarget.type === 'text';

        this.inputTarget.type = revealed ? 'password' : 'text';
        this.iconTarget.classList.toggle('bi-eye', revealed);
        this.iconTarget.classList.toggle('bi-eye-slash', !revealed);

        if (this.iconTarget.dataset.show) {
            this.iconTarget.textContent = revealed ? this.iconTarget.dataset.show : this.iconTarget.dataset.hide;
        }

        const label = revealed ? 'Afficher le mot de passe' : 'Masquer le mot de passe';
        this.buttonTarget.setAttribute('aria-label', label);
        this.buttonTarget.setAttribute('aria-pressed', String(!revealed));
    }
}
