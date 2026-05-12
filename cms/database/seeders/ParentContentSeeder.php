<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\Project;
use App\Models\Site;
use App\Models\SiteConfig;
use App\Models\TeamMember;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

/**
 * Seeds the Parent site (zeplow.com) with the content currently rendered by
 * apps/parent/ from packages/api/src/mock-data.ts. Once the API/CMS wire is
 * up and content has been resynced, the live Parent site can render this
 * content from the API instead of from the bundled mock.
 *
 * WARNING: Observers are suppressed during seeding via Model::withoutEvents().
 * Fire syncs deliberately afterwards by touching every published model — that
 * way we avoid burst-rate-limiting the API or the Cloudflare deploy hook.
 *
 *   php artisan db:seed --class=Database\\Seeders\\ParentContentSeeder
 */
class ParentContentSeeder extends Seeder
{
    public function run(): void
    {
        Model::withoutEvents(function () {
            $site = Site::where('key', 'parent')->firstOrFail();

            $this->seedSiteConfig($site);
            $this->seedPages($site);
            $this->seedProjects($site);
            $this->seedTeam($site);
        });
    }

    private function seedSiteConfig(Site $site): void
    {
        SiteConfig::updateOrCreate(
            ['site_id' => $site->id],
            [
                'nav_items' => [
                    ['label' => 'About', 'url' => '/about', 'is_external' => false],
                    ['label' => 'Ventures', 'url' => '/ventures', 'is_external' => false],
                    ['label' => 'Insights', 'url' => '/insights', 'is_external' => false],
                    ['label' => 'Careers', 'url' => '/careers', 'is_external' => false],
                    ['label' => 'Contact', 'url' => '/contact', 'is_external' => false],
                ],
                'footer_links' => [
                    [
                        'group_title' => 'Ventures',
                        'links' => [
                            ['label' => 'Zeplow Narrative', 'url' => 'https://narrative.zeplow.com'],
                            ['label' => 'Zeplow Logic', 'url' => 'https://logic.zeplow.com'],
                        ],
                    ],
                    [
                        'group_title' => 'Company',
                        'links' => [
                            ['label' => 'About', 'url' => '/about'],
                            ['label' => 'Insights', 'url' => '/insights'],
                            ['label' => 'Careers', 'url' => '/careers'],
                            ['label' => 'Contact', 'url' => '/contact'],
                        ],
                    ],
                ],
                'footer_text' => '© 2026 Zeplow LLC. All rights reserved.',
                'cta_text' => 'Get in Touch',
                'cta_url' => '/contact',
                'social_links' => [
                    'linkedin' => 'https://linkedin.com/company/zeplow',
                    'instagram' => 'https://instagram.com/zeplow',
                ],
                'contact_email' => 'hello@zeplow.com',
            ]
        );
    }

    private function seedPages(Site $site): void
    {
        $pages = [
            [
                'slug' => 'home',
                'title' => 'Home',
                'template' => 'home',
                'sort_order' => 0,
                'seo_title' => 'Zeplow — Story. Systems. Ventures.',
                'seo_description' => 'The company behind companies. Zeplow builds and operates ventures in brand storytelling and technology.',
                'content' => [
                    [
                        'type' => 'hero',
                        'data' => [
                            'heading' => 'Story. Systems. Ventures.',
                            'subheading' => 'We build and operate ventures that help businesses become household names.',
                            'button_text' => 'Get in Touch',
                            'button_url' => '/contact',
                        ],
                    ],
                    [
                        'type' => 'text',
                        'data' => [
                            'heading' => 'The company behind companies.',
                            'body' => '<p>At Zeplow, we believe that lasting impact comes from two forces working together — the art of narrative and the discipline of logic. Through our two venture arms, we help businesses build brands that resonate and systems that scale.</p>',
                        ],
                    ],
                    [
                        'type' => 'cards',
                        'data' => [
                            'heading' => 'Our Ventures',
                            'cards' => [
                                ['title' => 'Zeplow Narrative', 'description' => 'Brand storytelling, identity systems, and content that turns businesses into stories worth following.', 'url' => '/ventures/narrative'],
                                ['title' => 'Zeplow Logic', 'description' => 'Technology, automation, and AI systems that replace chaos with systems that run boringly well.', 'url' => '/ventures/logic'],
                            ],
                        ],
                    ],
                    [
                        'type' => 'cta',
                        'data' => [
                            'heading' => 'If this feels like your kind of thinking, we should talk.',
                            'button_text' => 'Get in Touch',
                            'button_url' => '/contact',
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'about',
                'title' => 'About',
                'template' => 'about',
                'sort_order' => 1,
                'seo_title' => 'About — Zeplow',
                'seo_description' => 'Learn about Zeplow, our vision, mission, values, and the team behind the ventures.',
                'content' => [
                    ['type' => 'hero', 'data' => ['heading' => 'About Zeplow']],
                    ['type' => 'text', 'data' => ['heading' => 'Our Vision', 'body' => '<p>To build an ecosystem where businesses don\'t just survive — they become household names through the combined power of compelling narrative and resilient systems.</p>']],
                    ['type' => 'text', 'data' => ['heading' => 'Our Mission', 'body' => '<p>To help businesses unlock their full potential through two disciplines: Narrative — the art of brand storytelling, and Logic — the science of scalable systems.</p>']],
                    [
                        'type' => 'stats',
                        'data' => [
                            'stats' => [
                                ['label' => 'Projects Delivered', 'value' => '50+'],
                                ['label' => 'Countries Served', 'value' => '12'],
                                ['label' => 'Venture Arms', 'value' => '2'],
                                ['label' => 'Founded', 'value' => '2024'],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'ventures',
                'title' => 'Our Ventures',
                'template' => 'ventures',
                'sort_order' => 2,
                'seo_title' => 'Our Ventures — Zeplow',
                'seo_description' => 'Zeplow operates through two specialized arms — Narrative for brand storytelling and Logic for technology systems.',
                'content' => [
                    ['type' => 'hero', 'data' => ['heading' => 'Our Ventures']],
                    ['type' => 'text', 'data' => ['body' => '<p>Zeplow operates through two specialized arms — each with its own expertise, but united by a single standard of quality.</p>']],
                    [
                        'type' => 'cards',
                        'data' => [
                            'cards' => [
                                ['title' => 'Zeplow Narrative', 'description' => 'Brand Storytelling, Identity & Content Systems. We help brands stop being invisible.', 'url' => '/ventures/narrative'],
                                ['title' => 'Zeplow Logic', 'description' => 'Technology, Automation & AI Systems. We replace spreadsheets and manual processes with systems that run boringly well.', 'url' => '/ventures/logic'],
                            ],
                        ],
                    ],
                    ['type' => 'cta', 'data' => ['heading' => 'Ready to work with us?', 'button_text' => 'Get in Touch', 'button_url' => '/contact']],
                ],
            ],
            [
                'slug' => 'ventures-narrative',
                'title' => 'Zeplow Narrative',
                'template' => 'ventures',
                'sort_order' => 3,
                'seo_title' => 'Zeplow Narrative — Stories that sell.',
                'seo_description' => 'Through Zeplow Narrative, we help brands stop being invisible with strategy, visual identity, content production, and ongoing management.',
                'content' => [
                    ['type' => 'hero', 'data' => ['heading' => 'Zeplow Narrative', 'subheading' => 'Stories that sell.']],
                    ['type' => 'text', 'data' => ['body' => '<p>We help brands stop being invisible. Through strategy, visual identity, content production, and ongoing management — we turn businesses into stories worth following.</p>']],
                    [
                        'type' => 'cards',
                        'data' => [
                            'heading' => 'What Narrative Does',
                            'cards' => [
                                ['title' => 'Brand Strategy & Positioning', 'description' => 'Define who you are and why it matters.'],
                                ['title' => 'Visual Identity Systems', 'description' => 'Logos, design systems, and brand guidelines that hold.'],
                                ['title' => 'Video & Photo Production', 'description' => 'Content that captures attention and tells your story.'],
                                ['title' => 'Content Direction & Calendars', 'description' => 'Strategic content planning that drives results.'],
                                ['title' => 'Social Media Management', 'description' => 'Consistent, on-brand presence across platforms.'],
                                ['title' => 'Campaign Creative', 'description' => 'Creative campaigns that move people to action.'],
                            ],
                        ],
                    ],
                    ['type' => 'cta', 'data' => ['heading' => 'Visit Zeplow Narrative', 'button_text' => 'Get in Touch', 'button_url' => '/contact']],
                ],
            ],
            [
                'slug' => 'ventures-logic',
                'title' => 'Zeplow Logic',
                'template' => 'ventures',
                'sort_order' => 4,
                'seo_title' => 'Zeplow Logic — Build once. Run forever.',
                'seo_description' => 'Through Zeplow Logic, we replace spreadsheets, manual processes, and operational chaos with systems that run boringly well.',
                'content' => [
                    ['type' => 'hero', 'data' => ['heading' => 'Zeplow Logic', 'subheading' => 'Build once. Run forever.']],
                    ['type' => 'text', 'data' => ['body' => '<p>We replace spreadsheets, manual processes, and operational chaos with systems that run boringly well.</p>']],
                    [
                        'type' => 'cards',
                        'data' => [
                            'heading' => 'What Logic Does',
                            'cards' => [
                                ['title' => 'Workflow Audits & Process Design', 'description' => 'Find the bottlenecks, design the fix.'],
                                ['title' => 'Custom Dashboards', 'description' => 'See your business in real time.'],
                                ['title' => 'ERP/CRM Systems', 'description' => 'All-in-one systems tailored to how you work.'],
                                ['title' => 'API Integrations', 'description' => 'Connect your tools so data flows automatically.'],
                                ['title' => 'AI-Native Automation', 'description' => 'Intelligent systems that learn and improve.'],
                                ['title' => 'MVP Development', 'description' => 'Launch fast, validate faster.'],
                                ['title' => 'Fractional CTO Services', 'description' => 'Senior technical leadership without the full-time cost.'],
                            ],
                        ],
                    ],
                    ['type' => 'cta', 'data' => ['heading' => 'Visit Zeplow Logic', 'button_text' => 'Get in Touch', 'button_url' => '/contact']],
                ],
            ],
            [
                'slug' => 'insights',
                'title' => 'Insights',
                'template' => 'insights',
                'sort_order' => 5,
                'seo_title' => 'Insights — Zeplow',
                'seo_description' => 'Thinking on narrative, systems, and the ventures we build.',
                'content' => [
                    ['type' => 'hero', 'data' => ['heading' => 'Insights', 'subheading' => 'Thinking on narrative, systems, and the ventures we build.']],
                ],
            ],
            [
                'slug' => 'careers',
                'title' => 'Careers',
                'template' => 'careers',
                'sort_order' => 6,
                'seo_title' => 'Careers at Zeplow',
                'seo_description' => 'Join the Zeplow team. We\'re building something ambitious.',
                'content' => [
                    ['type' => 'hero', 'data' => ['heading' => 'Careers at Zeplow']],
                    ['type' => 'text', 'data' => ['body' => '<p>We\'re a small, focused team building something ambitious. If you\'re interested in joining us, reach out directly.</p>']],
                    ['type' => 'cta', 'data' => ['heading' => 'Interested?', 'button_text' => 'Send us a message', 'button_url' => '/contact']],
                ],
            ],
            [
                'slug' => 'contact',
                'title' => 'Contact',
                'template' => 'contact',
                'sort_order' => 7,
                'seo_title' => 'Contact — Zeplow',
                'seo_description' => 'Get in touch with Zeplow. We\'d love to hear from you.',
                'content' => [
                    ['type' => 'hero', 'data' => ['heading' => 'Get in Touch', 'subheading' => 'Have a project in mind? We\'d love to hear from you.']],
                ],
            ],
        ];

        foreach ($pages as $data) {
            Page::updateOrCreate(
                ['site_id' => $site->id, 'slug' => $data['slug']],
                array_merge($data, [
                    'site_id' => $site->id,
                    'is_published' => true,
                    'published_at' => now(),
                ])
            );
        }
    }

    private function seedProjects(Site $site): void
    {
        // Featured portfolio items shown on zeplow.com — the same three flagship
        // projects also live on Logic, kept here so the parent site has a portfolio
        // without depending on the Logic CMS records.
        $projects = [
            [
                'slug' => 'tututor-ai',
                'title' => 'Tututor.ai',
                'one_liner' => 'AI-powered tutoring platform that personalizes learning and accelerates student success.',
                'client_name' => 'Tututor',
                'industry' => 'EdTech',
                'url' => 'https://tututor.ai',
                'challenge' => 'Tututor’s 1:1 model couldn’t scale. Tutors were buried under prep work, every student got the same generic plan, and growth meant linear hiring with no improvement in quality.',
                'solution' => 'We architected an AI-powered adaptive learning engine that diagnoses each student’s gaps, generates a personalized curriculum, and routes only the human-needed moments to tutors. Built around a clean teacher dashboard so tutors stay in the loop, never replaced.',
                'outcome' => '10x student engagement. 60% reduction in tutor prep workload. Operations now run with the same team at 4x the student count.',
                'tech_stack' => ['Next.js', 'Python', 'PostgreSQL', 'OpenAI'],
                'tags' => ['ai', 'edtech', 'platform'],
                'featured' => true,
                'sort_order' => 0,
            ],
            [
                'slug' => 'capec-ai',
                'title' => 'CAPEC AI',
                'one_liner' => 'Platform connecting emerging market businesses with global non-dilutive funding.',
                'client_name' => 'CAPEC',
                'industry' => 'FinTech',
                'url' => null,
                'challenge' => 'Emerging-market businesses are invisible to global non-dilutive funders. Discovery is manual, applications are repetitive, and matches are mostly accidental.',
                'solution' => 'A two-sided AI matching platform: structured business profiles on one side, funder mandates on the other, semantic matching + scored shortlists in between. Application templates auto-fill from the business profile.',
                'outcome' => 'Match-to-application time cut from weeks to hours. First funder cohort closed inside 90 days of launch.',
                'tech_stack' => ['Next.js', 'Python', 'AI Matching', 'PostgreSQL'],
                'tags' => ['ai', 'fintech', 'marketplace'],
                'featured' => true,
                'sort_order' => 1,
            ],
            [
                'slug' => 'aditio-erp',
                'title' => 'Aditio ERP',
                'one_liner' => 'Custom all-in-one ERP — project management, invoicing, team allocation in a single dashboard.',
                'client_name' => 'Aditio Agency',
                'industry' => 'SaaS',
                'url' => null,
                'challenge' => 'Aditio was running its agency on six disconnected tools — projects in one, invoices in another, team allocation in a spreadsheet. Every status update cost an hour of context-switching.',
                'solution' => 'A single custom ERP: projects, time, invoices, team allocation, and client comms in one dashboard. Built around Aditio’s actual workflow, not a generic template.',
                'outcome' => 'Six tools collapsed to one. Status meetings cut by 70%. Team allocation visible in real time.',
                'tech_stack' => ['Laravel', 'Vue.js', 'MySQL'],
                'tags' => ['erp', 'saas', 'dashboard'],
                'featured' => true,
                'sort_order' => 2,
            ],
        ];

        foreach ($projects as $data) {
            Project::updateOrCreate(
                ['site_id' => $site->id, 'slug' => $data['slug']],
                array_merge($data, [
                    'site_id' => $site->id,
                    'is_published' => true,
                ])
            );
        }
    }

    private function seedTeam(Site $site): void
    {
        // On the parent (group) site, Shadman is listed first — he's the brand-side
        // face. On Logic, Shakib leads; on Narrative, Shadman leads. The same two
        // founders are seeded with site-appropriate ordering.
        $members = [
            [
                'name' => 'Shadman Sakib',
                'role' => 'Co-Founder & CEO',
                'bio' => 'Strategy, direction, and brand & venture leadership. Leads strategy, client relationships, and brand direction across the Zeplow group.',
                'linkedin' => 'https://linkedin.com/in/shadmansakib',
                'email' => 'shadman@zeplow.com',
                'is_founder' => true,
                'sort_order' => 0,
            ],
            [
                'name' => 'Shakib Bin Kabir',
                'role' => 'Co-Founder & CTO',
                'bio' => 'Systems, automation, AI & technical architecture. Leads technology, product development, and infrastructure decisions across the Zeplow group.',
                'linkedin' => 'https://linkedin.com/in/shakibbinkabir',
                'email' => 'shakib@zeplow.com',
                'is_founder' => true,
                'sort_order' => 1,
            ],
        ];

        foreach ($members as $data) {
            TeamMember::updateOrCreate(
                ['site_id' => $site->id, 'email' => $data['email']],
                array_merge($data, ['site_id' => $site->id])
            );
        }
    }
}
