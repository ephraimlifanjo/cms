export function slugify(value: string): string {
  return value
    .normalize('NFKD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .trim()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '')
    .slice(0, 96);
}

export function formatDate(value?: string | null): string {
  if (!value) return '';
  return new Intl.DateTimeFormat('fr', { day: '2-digit', month: 'short', year: 'numeric' }).format(new Date(value));
}

export function initials(name = 'Nova'): string {
  return name.split(/\s+/).filter(Boolean).slice(0, 2).map((part) => part[0]?.toUpperCase()).join('');
}

export function readingTime(text = ''): number {
  return Math.max(1, Math.ceil(text.trim().split(/\s+/).filter(Boolean).length / 220));
}
