import { clsx } from "clsx";
import { twMerge } from "tailwind-merge";

export function cn(...inputs) {
  return twMerge(clsx(inputs));
}

// Utility functions for WHMCS admin
export const formatCurrency = (amount, currency = 'USD') => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: currency,
  }).format(amount);
};

export const formatDate = (date) => {
  return new Intl.DateTimeFormat('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  }).format(new Date(date));
};

export const formatDateTime = (date) => {
  return new Intl.DateTimeFormat('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }).format(new Date(date));
};

export const getStatusColor = (status) => {
  const colors = {
    active: 'green',
    suspended: 'red',
    pending: 'yellow',
    inactive: 'gray',
    paid: 'green',
    overdue: 'red',
    open: 'red',
    'in progress': 'yellow',
    resolved: 'green',
    closed: 'gray',
  };
  return colors[status.toLowerCase()] || 'gray';
};

export const truncateText = (text, maxLength = 50) => {
  if (text.length <= maxLength) return text;
  return text.substring(0, maxLength) + '...';
};

// CSV import utilities
export const parseCSV = (csvText) => {
  const lines = csvText.split('\n');
  const headers = lines[0].split(',').map(header => header.trim());
  const data = [];

  for (let i = 1; i < lines.length; i++) {
    if (lines[i].trim()) {
      const values = lines[i].split(',').map(value => value.trim());
      const row = {};
      headers.forEach((header, index) => {
        row[header] = values[index] || '';
      });
      data.push(row);
    }
  }

  return { headers, data };
};

// Validation utilities
export const isValidEmail = (email) => {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return emailRegex.test(email);
};

export const isValidPhone = (phone) => {
  const phoneRegex = /^[\+]?[1-9][\d]{0,15}$/;
  return phoneRegex.test(phone.replace(/[\s\-\(\)]/g, ''));
};

// Search and filter utilities
export const searchInObject = (obj, searchTerm) => {
  const term = searchTerm.toLowerCase();
  return Object.values(obj).some(value => 
    String(value).toLowerCase().includes(term)
  );
};

export const filterByStatus = (items, status) => {
  if (!status || status === 'all') return items;
  return items.filter(item => 
    item.status.toLowerCase() === status.toLowerCase()
  );
};

// Date utilities
export const getDaysFromNow = (date) => {
  const today = new Date();
  const targetDate = new Date(date);
  const diffTime = targetDate - today;
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
  return diffDays;
};

export const isDateExpired = (date) => {
  return new Date(date) < new Date();
};

export const isDateExpiringSoon = (date, days = 30) => {
  const daysUntil = getDaysFromNow(date);
  return daysUntil <= days && daysUntil > 0;
};