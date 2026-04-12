import { Container, Button } from '@zeplow/ui';
import type { Metadata } from 'next';

export const metadata: Metadata = {
  title: 'Page Not Found — Zeplow',
};

export default function NotFound() {
  return (
    <main className="flex min-h-[70vh] items-center pt-20">
      <Container className="text-center">
        <p className="text-[11px] font-semibold uppercase tracking-[0.2em] text-text/25">
          404
        </p>
        <h1 className="mt-4 font-heading text-4xl font-bold tracking-tight text-primary">
          Page not found
        </h1>
        <p className="mx-auto mt-4 max-w-sm text-text/40">
          The page you&apos;re looking for doesn&apos;t exist or has been moved.
        </p>
        <div className="mt-8">
          <Button href="/">Back to Home</Button>
        </div>
      </Container>
    </main>
  );
}
