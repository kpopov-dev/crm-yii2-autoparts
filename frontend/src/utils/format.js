const CURRENCY_SIGNS = {
  RUB: '₽',
  USD: '$',
  EUR: '€',
  CNY: '¥',
};

export function formatMoney(amount, currency) {
  const value = Number(amount || 0).toLocaleString('ru-RU', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
  });

  return `${value} ${CURRENCY_SIGNS[currency] || currency || '₽'}`;
}

export function formatDate(timestamp) {
  if (!timestamp) {
    return '—';
  }

  return new Date(timestamp * 1000).toLocaleDateString('ru-RU', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  });
}

export function formatDateTime(timestamp) {
  if (!timestamp) {
    return '—';
  }

  return new Date(timestamp * 1000).toLocaleString('ru-RU', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}

export function formatPercent(value) {
  return `${Number(value || 0).toFixed(1)} %`;
}
