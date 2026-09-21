import { Controller } from '@hotwired/stimulus';

/**
 * Copie un lien dans le presse-papiers, et le dit.
 */
export default class extends Controller {
    static targets = ['source', 'button'];

    async toClipboard() {
        this.sourceTarget.select();

        try {
            await navigator.clipboard.writeText(this.sourceTarget.value);
        } catch {
            // Sans autorisation, la sélection laisse copier à la main.
            return;
        }

        const label = this.buttonTarget.textContent;
        this.buttonTarget.textContent = 'Copié';

        setTimeout(() => {
            this.buttonTarget.textContent = label;
        }, 1500);
    }
}
