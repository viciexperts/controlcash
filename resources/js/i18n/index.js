import es from './es';

const dictionaries = { es };
const locale = import.meta.env.VITE_APP_LOCALE || 'es';

export const messages = dictionaries[locale] || dictionaries.es;

export function t(path) {
    return path.split('.').reduce((value, key) => value?.[key], messages) || path;
}
