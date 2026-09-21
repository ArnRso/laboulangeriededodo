import { Controller } from '@hotwired/stimulus';

/**
 * Réordonne le fil au glisser-déposer. Le nouvel ordre part au serveur dès
 * que la ligne est lâchée ; les flèches restent pour qui n'a pas de souris.
 */
export default class extends Controller {
    static targets = ['item'];
    static values = { url: String, token: String };

    connect() {
        this.dragged = null;
    }

    start(event) {
        this.dragged = event.currentTarget.closest('[data-sortable-target="item"]');
        this.dragged.classList.add('is-dragging');
        event.dataTransfer.effectAllowed = 'move';
        // Firefox n'amorce pas le glisser sans données.
        event.dataTransfer.setData('text/plain', '');
    }

    over(event) {
        if (!this.dragged) {
            return;
        }

        event.preventDefault();

        const target = event.currentTarget;

        if (target === this.dragged) {
            return;
        }

        // Au-dessus du milieu, la ligne se pose avant ; au-dessous, après.
        const box = target.getBoundingClientRect();
        const after = event.clientY > box.top + box.height / 2;

        target.parentNode.insertBefore(this.dragged, after ? target.nextSibling : target);
    }

    end() {
        if (!this.dragged) {
            return;
        }

        this.dragged.classList.remove('is-dragging');
        this.dragged = null;

        const body = new FormData();
        body.set('_token', this.tokenValue);

        for (const item of this.itemTargets) {
            body.append('ids[]', item.dataset.sortableId);
        }

        fetch(this.urlValue, { method: 'POST', body, credentials: 'same-origin' });
    }
}
