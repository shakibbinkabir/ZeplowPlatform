import React from 'react';

interface ButtonProps {
  children: React.ReactNode;
  href?: string;
  variant?: 'primary' | 'secondary' | 'ghost';
  className?: string;
  type?: 'button' | 'submit';
  disabled?: boolean;
  onClick?: () => void;
}

export function Button({
  children,
  href,
  variant = 'primary',
  className = '',
  type = 'button',
  disabled = false,
  onClick,
}: ButtonProps) {
  const base =
    'inline-flex items-center justify-center rounded-full font-body text-[13px] font-medium tracking-wide transition-all duration-300';
  const variants = {
    primary:
      'bg-primary text-white px-7 py-3 hover:bg-primary/90 hover:shadow-lg hover:shadow-primary/20',
    secondary:
      'border border-text/15 text-text/70 px-7 py-3 hover:border-primary hover:text-primary',
    ghost:
      'text-text/50 px-4 py-2 hover:text-primary underline underline-offset-4 decoration-text/20 hover:decoration-primary/40',
  };

  const styles = `${base} ${variants[variant]} ${className}`;

  if (href) {
    return (
      <a href={href} className={styles}>
        {children}
      </a>
    );
  }

  return (
    <button
      type={type}
      className={styles}
      disabled={disabled}
      onClick={onClick}
    >
      {children}
    </button>
  );
}
