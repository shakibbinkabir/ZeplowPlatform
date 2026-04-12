// Image types
export interface ProjectImage {
  original: string;
  large: string;
  medium: string;
  thumbnail: string;
  large_webp: string | null;
  alt: string;
}

export interface MediaImage {
  original: string;
  large: string;
  medium: string;
  thumbnail: string;
}

// Configuration
export interface SiteConfig {
  site_key: string;
  site_name: string;
  domain: string;
  tagline: string;
  nav_items: NavItem[];
  footer_links: FooterLinkGroup[];
  footer_text: string;
  cta_text: string;
  cta_url: string;
  social_links: Record<string, string>;
  contact_email: string;
}

export interface NavItem {
  label: string;
  url: string;
  is_external: boolean;
}

export interface FooterLinkGroup {
  group_title: string;
  links: { label: string; url: string }[];
}

// Pages
export interface PageListItem {
  id: number;
  slug: string;
  title: string;
  template: string;
  seo: SEO;
  sort_order: number;
  published_at: string;
}

export interface Page {
  id: number;
  slug: string;
  title: string;
  template: string;
  content: ContentBlock[];
  seo: SEO;
  published_at: string;
}

// Content Blocks
export interface ContentBlock {
  type:
    | 'hero'
    | 'text'
    | 'cards'
    | 'cta'
    | 'image'
    | 'gallery'
    | 'testimonials'
    | 'team'
    | 'projects'
    | 'stats'
    | 'divider'
    | 'raw_html';
  data: Record<string, unknown>;
}

export interface SEO {
  title: string;
  description: string;
  og_image: string | null;
}

// Projects
export interface ProjectListItem {
  id: number;
  slug: string;
  title: string;
  one_liner: string;
  client_name: string | null;
  industry: string | null;
  url: string | null;
  images: ProjectImage[];
  tags: string[];
  featured: boolean;
  sort_order: number;
}

export interface Project extends ProjectListItem {
  challenge: string | null;
  solution: string | null;
  outcome: string | null;
  tech_stack: string[];
  published_at: string;
}

// Blog Posts
export interface BlogPostListItem {
  id: number;
  slug: string;
  title: string;
  excerpt: string | null;
  cover_image: MediaImage | null;
  tags: string[];
  author: string | null;
  published_at: string;
}

export interface BlogPost extends BlogPostListItem {
  body: string;
  seo: SEO;
}

// Testimonials
export interface Testimonial {
  id: number;
  name: string;
  role: string | null;
  company: string | null;
  quote: string;
  avatar: MediaImage | null;
  sort_order: number;
}

// Team
export interface TeamMember {
  id: number;
  name: string;
  role: string;
  bio: string | null;
  photo: MediaImage | null;
  linkedin: string | null;
  email: string | null;
  is_founder: boolean;
  sort_order: number;
}

// Pagination
export interface PaginatedResponse<T> {
  data: T[];
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}
