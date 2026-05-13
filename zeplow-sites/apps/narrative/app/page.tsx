// PHASE 1 STUB — placeholder home page so the dev server has a route to
// render. Phase 3.1 will replace this with the real home (hero + Invisibility
// Tax block + featured projects + testimonials + CTA, sourced from the CMS).

export default function HomePage() {
  return (
    <main className="min-h-screen flex items-center justify-center px-6">
      <div className="max-w-xl text-center">
        <p className="text-sm uppercase tracking-[0.2em] text-accent mb-4">
          Zeplow Narrative
        </p>
        <h1 className="text-4xl md:text-5xl font-heading text-primary leading-tight mb-6">
          Stories that sell.
        </h1>
        <p className="text-text/70 leading-relaxed">
          This site is being built. The CMS is wired, the API is live, the
          design system is taking shape. Come back soon.
        </p>
      </div>
    </main>
  );
}
