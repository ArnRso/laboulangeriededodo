import { Controller } from '@hotwired/stimulus';

/**
 * Pile de notifications repliée : un clic déplie ce qui était caché.
 */
export default class extends Controller {
    static targets = ['button'];

    reveal() {
        for (const item of this.element.querySelectorAll('[hidden]')) {
            item.hidden = false;
        }

        this.buttonTarget.hidden = true;
    }
}
