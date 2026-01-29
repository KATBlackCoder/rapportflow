export function formatDate(iso: string | null): string {
    if (!iso) return '—';
    const d = new Date(iso);
    return new Intl.DateTimeFormat('fr-FR', {
        dateStyle: 'short',
        timeStyle: 'short',
    }).format(d);
}
