import React from 'react';

interface ContainerProps {
  children: React.ReactNode;
  className?: string;
  narrow?: boolean;
}

export function Container({ children, className = '', narrow = false }: ContainerProps) {
  return (
    <div
      className={`mx-auto px-6 lg:px-8 ${
        narrow ? 'max-w-3xl' : 'max-w-6xl'
      } ${className}`}
    >
      {children}
    </div>
  );
}
