import { createRoot } from 'react-dom/client';
import App from './components/App';
import './index.scss';

document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('lm-root');

    if (container) {
        const root = createRoot(container);
        root.render(<App />);
    }

});

