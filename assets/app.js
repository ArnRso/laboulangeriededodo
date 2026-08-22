import './stimulus_bootstrap.js';
import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap-icons/font/bootstrap-icons.min.css';
import './styles/app.css';
import * as bootstrap from 'bootstrap';

// La version ESM de Bootstrap n'initialise pas seule les composants déclarés
// par data-bs-toggle : on l'expose pour que dropdowns et collapses s'activent.
window.bootstrap = bootstrap;
