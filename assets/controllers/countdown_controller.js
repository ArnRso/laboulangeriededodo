import { Controller } from '@hotwired/stimulus';

/**
 * Affiche le temps restant avant qu'un souvenir ne devienne accessible,
 * et recharge la page au moment où il se débloque.
 */
export default class extends Controller {
    static targets = ['output'];
    static values = { availableAt: String };

    connect() {
        this.availableAt = new Date(this.availableAtValue);
        this.update();
        this.timer = setInterval(() => this.update(), 1000);
    }

    disconnect() {
        clearInterval(this.timer);
    }

    update() {
        const remaining = this.availableAt.getTime() - Date.now();

        if (remaining <= 0) {
            clearInterval(this.timer);
            window.location.reload();

            return;
        }

        this.outputTarget.textContent = this.format(remaining);
    }

    format(milliseconds) {
        const totalSeconds = Math.floor(milliseconds / 1000);
        const hours = Math.floor(totalSeconds / 3600);
        const minutes = Math.floor((totalSeconds % 3600) / 60);
        const seconds = totalSeconds % 60;

        if (hours >= 24) {
            const days = Math.floor(hours / 24);

            return `${days} j ${hours % 24} h`;
        }

        return [hours, minutes, seconds].map((value) => String(value).padStart(2, '0')).join(':');
    }
}
