console.groupCollapsed(
    '%c🔌 Bootstrap Initialization',
    'color: #4f46e5; font-weight: bold;'
);

// Axios
import axios from 'axios';
window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
console.log(
    '%cStatus:',
    'font-weight: bold;',
    'Axios configured.'
);

// Laravel Echo
import './echo';
console.log(
    '%cStatus:',
    'font-weight: bold;',
    'Laravel Echo configured.'
);