/**
 * Utilidades compartidas de formato de moneda (S/ — Sol peruano).
 */
export function formatCurrency(value) {
    return new Intl.NumberFormat('es-PE', { style: 'currency', currency: 'PEN' }).format(Number(value) || 0);
}

export const currency = formatCurrency;