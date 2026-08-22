import { Controller } from '@hotwired/stimulus';

/**
 * Bascule un champ mot de passe entre masqué et lisible.
 */
export default class extends Controller {
    static targets = ['input', 'icon', 'button'];

    toggle() {
        const revealed = this.inputTarget.type === 'text';

        this.inputTarget.type = revealed ? 'password' : 'text';
        this.iconTarget.classList.toggle('bi-eye', revealed);
        this.iconTarget.classList.toggle('bi-eye-slash', !revealed);

        const label = revealed ? 'Afficher le mot de passe' : 'Masquer le mot de passe';
        this.buttonTarget.setAttribute('aria-label', label);
        this.buttonTarget.setAttribute('aria-pressed', String(!revealed));
    }
}
