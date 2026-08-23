import { Controller } from '@hotwired/stimulus';

/**
 * Le coup de pouce de démonstration : taper « dodo » au clavier, ou garder
 * l'horloge appuyée deux secondes, fait arriver la notification suivante.
 */
export default class extends Controller {
    static targets = ['form', 'hint'];
    static values = {
        sequence: { type: String, default: 'dodo' },
        holdMs: { type: Number, default: 2000 },
    };

    connect() {
        this.typed = '';
        this.onKey = (event) => this.press(event);
        window.addEventListener('keydown', this.onKey);
    }

    disconnect() {
        window.removeEventListener('keydown', this.onKey);
        clearTimeout(this.holdTimer);
        clearTimeout(this.hintTimer);
    }

    press(event) {
        if (event.metaKey || event.ctrlKey || event.altKey || event.key.length !== 1) {
            return;
        }

        this.typed = (this.typed + event.key.toLowerCase()).slice(-this.sequenceValue.length);

        if (this.typed === this.sequenceValue) {
            this.typed = '';
            this.trigger();
        }
    }

    // Sur mobile, il n'y a pas de clavier : l'horloge tient lieu de bouton secret.
    holdStart() {
        clearTimeout(this.holdTimer);
        this.holdTimer = setTimeout(() => this.trigger(), this.holdMsValue);
    }

    holdEnd() {
        clearTimeout(this.holdTimer);
    }

    trigger() {
        if (this.hasHintTarget) {
            this.hintTarget.classList.add('is-visible');
            clearTimeout(this.hintTimer);
            this.hintTimer = setTimeout(() => this.hintTarget.classList.remove('is-visible'), 1200);
        }

        this.formTarget.requestSubmit();
    }
}
