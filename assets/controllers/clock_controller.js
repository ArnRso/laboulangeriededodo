import { Controller } from '@hotwired/stimulus';

/**
 * L'horloge et la date du fil, reprises du téléphone plutôt que du serveur.
 */
export default class extends Controller {
    static targets = ['time', 'date'];

    connect() {
        this.tick();
        this.timer = setInterval(() => this.tick(), 15000);
    }

    disconnect() {
        clearInterval(this.timer);
    }

    tick() {
        const now = new Date();

        this.timeTarget.textContent = now.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });

        if (this.hasDateTarget) {
            this.dateTarget.textContent = now.toLocaleDateString('fr-FR', {
                weekday: 'long',
                day: 'numeric',
                month: 'long',
            });
        }
    }
}
