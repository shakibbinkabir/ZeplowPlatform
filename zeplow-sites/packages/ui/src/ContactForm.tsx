'use client';

import { useState, type FormEvent } from 'react';

interface ContactFormProps {
  siteKey: string;
  siteDomain: string;
}

const API_BASE = process.env.NEXT_PUBLIC_API_URL || 'https://api.zeplow.com';

export function ContactForm({ siteKey, siteDomain }: ContactFormProps) {
  const [formData, setFormData] = useState({
    name: '',
    email: '',
    company: '',
    message: '',
    budget_range: '',
    website_url: '',
  });
  const [status, setStatus] = useState<
    'idle' | 'submitting' | 'success' | 'error'
  >('idle');
  const [errorMessage, setErrorMessage] = useState('');

  async function handleSubmit(e: FormEvent) {
    e.preventDefault();
    setStatus('submitting');
    setErrorMessage('');

    if (formData.website_url) {
      setStatus('success');
      return;
    }

    try {
      const res = await fetch(
        `${API_BASE}/sites/v1/${siteKey}/contact`,
        {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
          },
          body: JSON.stringify({
            name: formData.name,
            email: formData.email,
            company: formData.company || undefined,
            message: formData.message,
            budget_range: formData.budget_range || undefined,
          }),
        }
      );

      if (res.ok) {
        setStatus('success');
        setFormData({
          name: '',
          email: '',
          company: '',
          message: '',
          budget_range: '',
          website_url: '',
        });
      } else if (res.status === 422) {
        const data = await res.json();
        const errors = data.errors || {};
        const firstError = Object.values(errors).flat()[0] as string;
        setErrorMessage(firstError || 'Please check your input.');
        setStatus('error');
      } else {
        throw new Error('Server error');
      }
    } catch {
      setErrorMessage(
        `Something went wrong. Please try again or email us directly at hello@${siteDomain}`
      );
      setStatus('error');
    }
  }

  if (status === 'success') {
    return (
      <div className="py-20 text-center">
        <p className="text-[11px] font-semibold uppercase tracking-[0.2em] text-accent/70">
          Message sent
        </p>
        <h3 className="mt-4 font-heading text-3xl font-bold tracking-tight text-primary">
          Thank you.
        </h3>
        <p className="mt-3 text-text/45">
          We&apos;ll be in touch within 24 hours.
        </p>
      </div>
    );
  }

  const inputStyles =
    'w-full rounded-xl border border-text/[0.08] bg-white px-5 py-3.5 text-[15px] text-text placeholder:text-text/25 transition-all duration-200 focus:border-primary/30 focus:outline-none focus:ring-2 focus:ring-primary/5';

  return (
    <form onSubmit={handleSubmit} className="space-y-5">
      {/* Honeypot */}
      <div className="absolute -left-[9999px]" aria-hidden="true">
        <label htmlFor="website_url">Website</label>
        <input
          type="text"
          id="website_url"
          name="website_url"
          value={formData.website_url}
          onChange={(e) =>
            setFormData({ ...formData, website_url: e.target.value })
          }
          tabIndex={-1}
          autoComplete="off"
        />
      </div>

      <div className="grid gap-5 md:grid-cols-2">
        <div>
          <label
            htmlFor="name"
            className="mb-2 block text-[13px] font-medium text-text/40"
          >
            Name *
          </label>
          <input
            type="text"
            id="name"
            required
            value={formData.name}
            onChange={(e) =>
              setFormData({ ...formData, name: e.target.value })
            }
            placeholder="Your name"
            className={inputStyles}
          />
        </div>
        <div>
          <label
            htmlFor="email"
            className="mb-2 block text-[13px] font-medium text-text/40"
          >
            Email *
          </label>
          <input
            type="email"
            id="email"
            required
            value={formData.email}
            onChange={(e) =>
              setFormData({ ...formData, email: e.target.value })
            }
            placeholder="you@company.com"
            className={inputStyles}
          />
        </div>
      </div>

      <div className="grid gap-5 md:grid-cols-2">
        <div>
          <label
            htmlFor="company"
            className="mb-2 block text-[13px] font-medium text-text/40"
          >
            Company
          </label>
          <input
            type="text"
            id="company"
            value={formData.company}
            onChange={(e) =>
              setFormData({ ...formData, company: e.target.value })
            }
            placeholder="Optional"
            className={inputStyles}
          />
        </div>
        <div>
          <label
            htmlFor="budget_range"
            className="mb-2 block text-[13px] font-medium text-text/40"
          >
            Budget
          </label>
          <select
            id="budget_range"
            value={formData.budget_range}
            onChange={(e) =>
              setFormData({ ...formData, budget_range: e.target.value })
            }
            className={`${inputStyles} ${
              !formData.budget_range ? 'text-text/25' : ''
            }`}
          >
            <option value="">Select range</option>
            <option value="Under $3,000">Under $3,000</option>
            <option value="$3,000–$5,000">$3,000 – $5,000</option>
            <option value="$5,000–$10,000">$5,000 – $10,000</option>
            <option value="$10,000+">$10,000+</option>
          </select>
        </div>
      </div>

      <div>
        <label
          htmlFor="message"
          className="mb-2 block text-[13px] font-medium text-text/40"
        >
          Message *
        </label>
        <textarea
          id="message"
          required
          rows={5}
          value={formData.message}
          onChange={(e) =>
            setFormData({ ...formData, message: e.target.value })
          }
          placeholder="Tell us about your project..."
          className={`${inputStyles} resize-none`}
        />
      </div>

      {errorMessage && (
        <p className="text-[13px] text-red-500/80">{errorMessage}</p>
      )}

      <button
        type="submit"
        disabled={status === 'submitting'}
        className="rounded-full bg-primary px-8 py-3.5 text-[13px] font-medium tracking-wide text-white transition-all duration-300 hover:bg-primary/90 hover:shadow-lg hover:shadow-primary/20 disabled:opacity-40"
      >
        {status === 'submitting' ? 'Sending...' : 'Send Message'}
      </button>
    </form>
  );
}
