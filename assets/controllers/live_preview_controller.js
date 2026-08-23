import { Controller } from '@hotwired/stimulus';

/**
 * Aperçu en direct d'une notification : à chaque modification du formulaire,
 * le serveur rend l'écran d'ouverture tel que le destinataire le verra, et le
 * résultat s'affiche dans le téléphone de droite.
 *
 * Le fichier choisi n'est pas envoyé à chaque frappe : il est montré
 * directement depuis le navigateur, à la place de la source rendue.
 */
export default class extends Controller {
    static targets = ['form', 'frame'];
    static values = {
        url: String,
        delay: { type: Number, default: 300 },
    };

    connect() {
        this.refresh();
    }

    disconnect() {
        clearTimeout(this.timer);
        this.abortController?.abort();
        this.forgetFile();
    }

    refresh() {
        clearTimeout(this.timer);
        this.timer = setTimeout(() => this.render(), this.delayValue);
    }

    pickFile(event) {
        this.forgetFile();

        const file = event.target.files[0];
        if (file) {
            this.fileUrl = URL.createObjectURL(file);
        }

        this.refresh();
    }

    async render() {
        this.abortController?.abort();
        this.abortController = new AbortController();

        const body = new FormData(this.formTarget);
        for (const [name, value] of [...body.entries()]) {
            if (value instanceof File) {
                body.delete(name);
            }
        }

        try {
            const response = await fetch(this.urlValue, {
                method: 'POST',
                body,
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                signal: this.abortController.signal,
            });
            this.frameTarget.srcdoc = await response.text();
        } catch (error) {
            if (error.name !== 'AbortError') {
                throw error;
            }
        }
    }

    showFile() {
        if (!this.fileUrl) {
            return;
        }

        const document = this.frameTarget.contentDocument;
        for (const element of document.querySelectorAll(
            '.f-media img, .f-media video, .f-media audio, .td-photo img',
        )) {
            element.src = this.fileUrl;
        }
    }

    forgetFile() {
        if (this.fileUrl) {
            URL.revokeObjectURL(this.fileUrl);
            this.fileUrl = null;
        }
    }
}
