/**
 * WhatsApp Helper Utilities for Semarak Aliyah
 * Automatically formats Indonesian numbers (e.g. 081254211xxx -> 6281254211xxx)
 */

export function formatWaNumber(phone, defaultNumber = '6283850070767') {
  if (!phone) return defaultNumber;

  let raw = String(phone).trim();
  if (!raw) return defaultNumber;

  // Extract phone number pattern if surrounded by text (e.g., "Pak Budi 0812-xxx")
  const match = raw.match(/(?:(?:\+?62)|0)?8[0-9\s-]{7,15}/);
  if (match) {
    raw = match[0];
  }

  // Strip all non-digit characters
  let digits = raw.replace(/\D/g, '');
  if (!digits) return defaultNumber;

  // Handle case where user wrote +6208... or 6208...
  if (digits.startsWith('620')) {
    digits = '62' + digits.substring(3);
  } else if (digits.startsWith('0')) {
    // 0812... -> 62812...
    digits = '62' + digits.replace(/^0+/, '');
  } else if (!digits.startsWith('62')) {
    // 812... -> 62812...
    digits = '62' + digits;
  }

  return digits;
}

export function getWaChatUrl(phone, text = '', defaultNumber = '6283850070767') {
  const cleanPhone = formatWaNumber(phone, defaultNumber);
  if (!text) {
    return `https://wa.me/${cleanPhone}`;
  }
  return `https://wa.me/${cleanPhone}?text=${encodeURIComponent(text)}`;
}
