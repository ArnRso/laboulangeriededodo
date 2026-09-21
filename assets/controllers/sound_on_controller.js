import { Controller } from '@hotwired/stimulus';

/**
 * Une vidéo qui démarre avec le son. Les navigateurs qui l'interdisent sans
 * geste préalable la laissent muette : on repart alors en sourdine, et le son
 * s'allume au premier contact avec la page — comme sur les apps de vidéo.
 */
export default class extends Controller {
    connect() {
        this.element.play().catch(() => this.startMuted());
    }

    disconnect() {
        this.stopWaiting();
    }

    /**
     * Le navigateur a refusé le son : la vidéo part muette, et attend un geste.
     */
    startMuted() {
        this.element.muted = true;
        this.element.play().catch(() => {});

        this.unmute = () => {
            this.element.muted = false;
            this.stopWaiting();
        };

        for (const event of ['pointerdown', 'keydown', 'touchstart']) {
            document.addEventListener(event, this.unmute, { once: true, passive: true });
        }
    }

    stopWaiting() {
        if (!this.unmute) {
            return;
        }

        for (const event of ['pointerdown', 'keydown', 'touchstart']) {
            document.removeEventListener(event, this.unmute);
        }

        this.unmute = null;
    }
}
