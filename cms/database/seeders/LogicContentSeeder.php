<?php

namespace Database\Seeders;

use App\Models\BlogPost;
use App\Models\Page;
use App\Models\Project;
use App\Models\Site;
use App\Models\SiteConfig;
use App\Models\TeamMember;
use App\Models\Testimonial;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

/**
 * Seeds the Logic site (logic.zeplow.com) with the content specified in
 * Logic_Site_PRD.md §16. Mirrors the mock data in zeplow-sites/packages/api/src/mock-data.ts
 * so that once the API app exists and a "Resync All" is triggered from Filament,
 * the live Logic site renders identical content to what's currently served from mocks.
 *
 * WARNING: Observers are suppressed during seeding via Model::withoutEvents().
 * Otherwise every save() would dispatch a SyncContentJob to api.zeplow.com — and until
 * that API exists, those jobs fail 3× each and pollute the sync_logs table.
 *
 * Run after SiteSeeder so the parent Site row exists.
 *
 *   php artisan db:seed --class=Database\\Seeders\\LogicContentSeeder
 */
class LogicContentSeeder extends Seeder
{
    public function run(): void
    {
        Model::withoutEvents(function () {
            $site = Site::where('key', 'logic')->firstOrFail();

            $this->seedSiteConfig($site);
            $this->seedPages($site);
            $this->seedProjects($site);
            $this->seedTeam($site);
            $this->seedTestimonials($site);
            $this->seedBlogPosts($site);
        });
    }

    private function seedSiteConfig(Site $site): void
    {
        SiteConfig::updateOrCreate(
            ['site_id' => $site->id],
            [
                'nav_items' => [
                    ['label' => 'About', 'url' => '/about', 'is_external' => false],
                    ['label' => 'Services', 'url' => '/services', 'is_external' => false],
                    ['label' => 'Work', 'url' => '/work', 'is_external' => false],
                    ['label' => 'Process', 'url' => '/process', 'is_external' => false],
                    ['label' => 'Insights', 'url' => '/insights', 'is_external' => false],
                    ['label' => 'Contact', 'url' => '/contact', 'is_external' => false],
                ],
                'footer_links' => [
                    [
                        'group_title' => 'The Zeplow Group',
                        'links' => [
                            ['label' => 'Zeplow', 'url' => 'https://zeplow.com'],
                            ['label' => 'Zeplow Narrative', 'url' => 'https://narrative.zeplow.com'],
                            ['label' => 'Zeplow Logic', 'url' => 'https://logic.zeplow.com'],
                        ],
                    ],
                    [
                        'group_title' => 'Company',
                        'links' => [
                            ['label' => 'About', 'url' => '/about'],
                            ['label' => 'Services', 'url' => '/services'],
                            ['label' => 'Work', 'url' => '/work'],
                            ['label' => 'Process', 'url' => '/process'],
                            ['label' => 'Insights', 'url' => '/insights'],
                            ['label' => 'Contact', 'url' => '/contact'],
                        ],
                    ],
                ],
                'footer_text' => '© 2026 Zeplow LLC. All rights reserved.',
                'cta_text' => 'Book a Systems Audit',
                'cta_url' => '/contact',
                'social_links' => [
                    'linkedin' => 'https://linkedin.com/company/zeplow',
                    'instagram' => 'https://instagram.com/zeplow',
                    'whatsapp' => 'https://wa.me/8801XXXXXXXXX',
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
                'seo_title' => 'Zeplow Logic — Build once. Run forever.',
                'seo_description' => 'Your Fractional Tech Co-Founder. We replace operational chaos with AI-native systems that run boringly well.',
                'content' => [
                    [
                        'type' => 'hero',
                        'data' => [
                            'heading' => 'Stop running a million-dollar vision on a ten-dollar spreadsheet.',
                            'subheading' => 'We are your Fractional Tech Co-Founder — replacing manual chaos with AI-native automation.',
                            'button_text' => 'Book a Systems Audit',
                            'button_url' => '/contact',
                        ],
                    ],
                    [
                        'type' => 'text',
                        'data' => [
                            'heading' => 'The diagnosis.',
                            'body' => '<p>Most companies think they need more developers. They actually need a proper architecture. The chaos in your inbox, your spreadsheets, your repeated daily decisions — it isn’t a staffing problem. It’s an absence of systems.</p><p>We diagnose the bottleneck, design the architecture, and build the operating system your business should have started with.</p>',
                        ],
                    ],
                    [
                        'type' => 'cards',
                        'data' => [
                            'heading' => 'The 4-pillar promise.',
                            'cards' => [
                                ['title' => 'We Speak Business, Not Just Code', 'description' => 'The Consultant Approach. We translate business problems into systems, not specs into tickets. You don’t need to learn engineering — we do the translation.'],
                                ['title' => 'From Idea to MVP in Days, Not Months', 'description' => 'The Speed. Fixed scope, fixed price, real users in four weeks. We ship working software, not roadmaps.'],
                                ['title' => 'Future-Proofed with AI', 'description' => 'The Innovation. Every system we build is AI-native by default — predictive, adaptive, leverageable. Not bolted on later.'],
                                ['title' => 'Invisible Operations', 'description' => 'The Peace of Mind. The best systems are the ones you forget exist. Monitored, documented, and boringly stable.'],
                            ],
                        ],
                    ],
                    [
                        'type' => 'cta',
                        'data' => [
                            'heading' => 'If your business runs on duct tape and spreadsheets, let’s fix that.',
                            'subheading' => 'Engagements start at $3,000. We do not offer hourly billing.',
                            'button_text' => 'Book a Systems Audit',
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
                'seo_title' => 'About — Zeplow Logic',
                'seo_description' => 'We bridge the gap between business vision and technical execution. Our purpose, vision, mission, and the team behind the systems.',
                'content' => [
                    ['type' => 'hero', 'data' => ['heading' => 'About Zeplow Logic', 'subheading' => 'The Systems Architect. Calm. Absolute. Clinical.']],
                    ['type' => 'text', 'data' => ['heading' => 'Purpose.', 'body' => '<p>To bridge the gap between business vision and technical execution, ensuring no great company fails due to Technical Paralysis.</p>']],
                    ['type' => 'text', 'data' => ['heading' => 'Vision.', 'body' => '<p>A world of Lean Giants — companies generating enterprise revenue with small teams because they run on Self-Driving operations.</p>']],
                    ['type' => 'text', 'data' => ['heading' => 'Mission.', 'body' => '<p>To replace manual grunt work with scalable clarity, functioning as the permanent, fractional engineering partner for growth-focused founders.</p>']],
                    [
                        'type' => 'stats',
                        'data' => [
                            'stats' => [
                                ['label' => 'Projects Delivered', 'value' => '17+'],
                                ['label' => 'Hours Automated', 'value' => '40k+'],
                                ['label' => 'Systems Uptime', 'value' => '99.9%'],
                                ['label' => 'Countries Served', 'value' => '8'],
                            ],
                        ],
                    ],
                    ['type' => 'text', 'data' => ['heading' => 'Values.', 'body' => '<p><strong>Stewardship Over Revenue.</strong> We protect your budget like it’s ours. If a feature doesn’t create leverage, we’ll say no.</p><p><strong>Systems Over Heroes.</strong> We don’t rely on late-night hacks. We build processes that work even when no one’s watching.</p><p><strong>Radical Transparency.</strong> Bad news travels fast here. You’ll always know where things stand.</p>']],
                ],
            ],
            [
                'slug' => 'services',
                'title' => 'Services',
                'template' => 'services',
                'sort_order' => 2,
                'seo_title' => 'Services — Zeplow Logic',
                'seo_description' => 'Three engagement types, eight service categories. Not a feature list — a business outcome map. Engagements start at $3,000.',
                'content' => [
                    ['type' => 'hero', 'data' => ['heading' => 'What We Build', 'subheading' => 'We don’t sell code. We sell operational freedom.']],
                    ['type' => 'text', 'data' => ['body' => '<p>Engagements start at $3,000. We do not offer hourly billing. This is the filter — if a fixed price feels expensive for the leverage you’ll gain, this isn’t the right fit.</p>']],
                    [
                        'type' => 'cards',
                        'data' => [
                            'heading' => 'Engagement types.',
                            'cards' => [
                                ['title' => 'The Discovery Audit', 'description' => 'We analyze your current manual processes and map out an Automation & Tech Roadmap. You see where you’re losing money before we touch a line of code. — $500–$1,000'],
                                ['title' => 'The MVP Sprint', 'description' => 'We build the V1 of your AI-integrated platform in 4 weeks. Fixed scope, fixed price, real users at the end. — $3,000–$10,000'],
                                ['title' => 'The Co-Founder Retainer', 'description' => 'We manage the app, fix bugs instantly, and release new AI features every month. Your entire tech department for a flat monthly fee. — $1,500–$3,000/mo'],
                            ],
                        ],
                    ],
                    [
                        'type' => 'cards',
                        'data' => [
                            'heading' => 'Service categories.',
                            'cards' => [
                                ['title' => 'Workflow Audits & Process Design', 'description' => 'Find the bottlenecks. Document the current state. Prescribe the fix.'],
                                ['title' => 'Custom Dashboards & Admin Panels', 'description' => 'See your business in real time. One screen, no spreadsheets.'],
                                ['title' => 'ERP / CRM Systems', 'description' => 'All-in-one operating systems tailored to how you actually work.'],
                                ['title' => 'API Integrations & Data Pipelines', 'description' => 'Connect your tools so data flows automatically — not by copy-paste.'],
                                ['title' => 'AI-Native Automation', 'description' => 'Predictive, adaptive systems that learn from your operations and improve them.'],
                                ['title' => 'MVP Development', 'description' => 'Launch fast. Validate faster. Real users in four weeks.'],
                                ['title' => 'Fractional CTO Services', 'description' => 'Senior technical leadership without the full-time hire.'],
                                ['title' => 'Ongoing Monitoring & Optimization', 'description' => 'Boring stability. We watch the system, you watch the business.'],
                            ],
                        ],
                    ],
                    ['type' => 'text', 'data' => ['heading' => 'The Leverage Check.', 'body' => '<p>Every feature we ship must pass three gates:</p><p><strong>The ROI Gate.</strong> Does this make money or save time?</p><p><strong>The Scale Gate.</strong> Will this break if the client grows 10x?</p><p><strong>The Complexity Gate.</strong> Can we build this simpler?</p>']],
                    [
                        'type' => 'cta',
                        'data' => [
                            'heading' => 'Start with a Systems Audit.',
                            'subheading' => 'We diagnose the bottleneck before we touch a line of code.',
                            'button_text' => 'Book a Systems Audit',
                            'button_url' => '/contact',
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'work',
                'title' => 'Our Work',
                'template' => 'work',
                'sort_order' => 3,
                'seo_title' => 'Our Work — Zeplow Logic',
                'seo_description' => 'Systems, automation, and AI projects that replaced chaos with clarity. Every project below solved a real business problem. No vanity features.',
                'content' => [
                    ['type' => 'hero', 'data' => ['heading' => 'Our Work', 'subheading' => 'Every project below solved a real business problem. No vanity features.']],
                ],
            ],
            [
                'slug' => 'process',
                'title' => 'Our Process',
                'template' => 'process',
                'sort_order' => 4,
                'seo_title' => 'Our Process — Zeplow Logic',
                'seo_description' => 'Every engagement follows the same 6-step framework. No shortcuts. No guessing. We measure twice so we only cut once.',
                'content' => [
                    ['type' => 'hero', 'data' => ['heading' => 'Our Process', 'subheading' => 'We measure twice so we only cut once.']],
                    ['type' => 'text', 'data' => ['body' => '<p>Every engagement follows the same 6-step framework. No shortcuts. No guessing.</p>']],
                    [
                        'type' => 'cards',
                        'data' => [
                            'heading' => 'The six steps.',
                            'cards' => [
                                ['title' => '01 · Discovery', 'description' => 'We start by listening. What’s broken? What keeps you up at night? We map your current state before proposing anything.'],
                                ['title' => '02 · Strategy', 'description' => 'We don’t guess. We diagnose the root cause, define the Definition of Done, and build a roadmap with clear milestones.'],
                                ['title' => '03 · Architecture', 'description' => 'The blueprint phase. Data flow, system design, tech stack decisions. We measure twice so we only cut once.'],
                                ['title' => '04 · Execution', 'description' => 'We build. You get updates, not meetings. Focused sprints — shipping real output every week.'],
                                ['title' => '05 · Delivery', 'description' => 'Deployed, documented, and stable. Tested and ready to perform.'],
                                ['title' => '06 · Partnership', 'description' => 'Launch isn’t the end. We monitor, optimize, and grow your systems monthly. We resell our value every 30 days.'],
                            ],
                        ],
                    ],
                    ['type' => 'cta', 'data' => ['heading' => 'Ready to start with Discovery?', 'button_text' => 'Book a Systems Audit', 'button_url' => '/contact']],
                ],
            ],
            [
                'slug' => 'insights',
                'title' => 'Insights',
                'template' => 'insights',
                'sort_order' => 5,
                'seo_title' => 'Insights — Zeplow Logic',
                'seo_description' => 'Technical thinking on automation, AI systems, and building operations that scale.',
                'content' => [
                    ['type' => 'hero', 'data' => ['heading' => 'Insights', 'subheading' => 'Technical thinking on automation, AI systems, and building operations that scale.']],
                ],
            ],
            [
                'slug' => 'contact',
                'title' => 'Contact',
                'template' => 'contact',
                'sort_order' => 6,
                'seo_title' => 'Contact — Zeplow Logic',
                'seo_description' => 'Book a Systems Audit. We’ll map your current state and show you where the leverage is — before a single line of code is written.',
                'content' => [
                    ['type' => 'hero', 'data' => ['heading' => 'Let’s Talk Architecture', 'subheading' => 'Book a Systems Audit — we’ll map your current state and show you where the leverage is.']],
                    ['type' => 'text', 'data' => ['body' => '<p>Reach us at <a href="mailto:hello@zeplow.com">hello@zeplow.com</a>, or send the form below. We reply within 24 hours.</p>']],
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
            [
                'slug' => 'rats-vault',
                'title' => "RAT'S Vault",
                'one_liner' => 'Secure digital platform for managing and protecting critical business data.',
                'client_name' => "RAT'S BD",
                'industry' => 'Enterprise',
                'url' => null,
                'challenge' => 'RAT’S was protecting sensitive client data across email, shared drives, and contractor handoffs. Compliance was theatre — there was no single source of truth and no audit trail.',
                'solution' => 'A purpose-built vault with role-based access, encrypted-at-rest storage, signed audit logs for every read/write, and a clean admin panel for the compliance officer.',
                'outcome' => 'Sensitive-data sprawl eliminated. First compliance review passed without remediation.',
                'tech_stack' => ['Laravel', 'React', 'MySQL'],
                'tags' => ['security', 'enterprise', 'data'],
                'featured' => false,
                'sort_order' => 3,
            ],
            [
                'slug' => 'atme-cards',
                'title' => 'ATME Cards',
                'one_liner' => 'Modern digital business card platform — share identity, links, and presence instantly.',
                'client_name' => 'ATME',
                'industry' => 'SaaS',
                'url' => null,
                'challenge' => 'Paper business cards lose their owners and offer zero analytics. Existing digital options were either too clinical or too gimmicky for a brand-conscious audience.',
                'solution' => 'A digital business-card platform with a brand-grade card editor, instant-share NFC/QR, contact-capture analytics, and team-level admin for agencies.',
                'outcome' => 'Shipped MVP in five weeks. First 1,000 users onboarded before paid acquisition started.',
                'tech_stack' => ['Next.js', 'Node.js', 'MongoDB'],
                'tags' => ['saas', 'platform'],
                'featured' => false,
                'sort_order' => 4,
            ],
            [
                'slug' => 'centrepoint-shop',
                'title' => 'CentrePoint Shop',
                'one_liner' => 'Full e-commerce store for a Canadian postal service.',
                'client_name' => 'CentrePoint Postal',
                'industry' => 'E-Commerce',
                'url' => null,
                'challenge' => 'CentrePoint Postal needed a full retail storefront layered on top of postal services — shipping rules, custom checkout flow, and Canadian tax handling — without locking themselves into a rigid SaaS template.',
                'solution' => 'A Shopify build with a custom theme, custom shipping logic for postal integration, and a streamlined checkout tuned for the postal-counter use case.',
                'outcome' => 'Online revenue stream live within the launch window. Counter staff trained in under an hour.',
                'tech_stack' => ['Shopify', 'Custom Theme', 'Liquid'],
                'tags' => ['ecommerce', 'shopify'],
                'featured' => false,
                'sort_order' => 5,
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
        // On Logic, Shakib is listed first (he's the delivery owner for Logic).
        $members = [
            [
                'name' => 'Shakib Bin Kabir',
                'role' => 'Co-Founder & CTO',
                'bio' => 'Systems, automation, AI & technical architecture. Leads technology, product development, and infrastructure decisions across the Zeplow group. Turns business problems into technical solutions that scale.',
                'linkedin' => 'https://linkedin.com/in/shakibbinkabir',
                'email' => 'shakib@zeplow.com',
                'is_founder' => true,
                'sort_order' => 0,
            ],
            [
                'name' => 'Shadman Sakib',
                'role' => 'Co-Founder & CEO',
                'bio' => 'Strategy, direction, and brand & venture leadership. Leads strategy, client relationships, and brand direction across the Zeplow group.',
                'linkedin' => 'https://linkedin.com/in/shadmansakib',
                'email' => 'shadman@zeplow.com',
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

    private function seedTestimonials(Site $site): void
    {
        $items = [
            [
                'name' => 'Tanvir Hasan',
                'role' => 'Founder',
                'company' => 'Aditio Agency',
                'quote' => 'Our business finally feels under control.',
                'sort_order' => 0,
            ],
            [
                'name' => 'Rashed Karim',
                'role' => 'Operations Director',
                'company' => "RAT'S BD",
                'quote' => 'They pushed back on half of what we asked for — and every one of those nos saved us money. That’s the difference between a vendor and a tech co-founder.',
                'sort_order' => 1,
            ],
            [
                'name' => 'Adiba Rahman',
                'role' => 'CEO',
                'company' => 'Tututor',
                'quote' => 'We went from drowning in tutor prep work to growing 4x with the same team. The systems quietly run the company now.',
                'sort_order' => 2,
            ],
        ];

        foreach ($items as $data) {
            Testimonial::updateOrCreate(
                ['site_id' => $site->id, 'name' => $data['name']],
                array_merge($data, ['site_id' => $site->id, 'is_published' => true])
            );
        }
    }

    private function seedBlogPosts(Site $site): void
    {
        $posts = [
            [
                'slug' => 'order-out-of-chaos',
                'title' => 'Order out of Chaos: Why most ops debt is actually architecture debt',
                'excerpt' => 'You don’t need more hires. You need a system. A field guide to spotting architecture debt before it becomes a hiring spree.',
                'body' => '<h2>The diagnosis</h2><p>When founders say "we need to hire more engineers," what they usually mean is "our existing engineers are buried under work that shouldn’t exist." The work shouldn’t exist because there’s no system absorbing it — every request hits a human.</p><h2>The pattern</h2><p>Repetitive Slack messages asking for data are a missing dashboard. Manual CSV exports are a missing API. Friday emergencies are a missing alert. None of these are staffing problems.</p><h2>The fix</h2><p>Before the next hire: audit one week of your team’s tickets. Categorize each as <strong>creative</strong>, <strong>judgement</strong>, or <strong>plumbing</strong>. Plumbing is your architecture debt. Build that out and the hiring problem usually solves itself.</p>',
                'tags' => ['systems', 'architecture'],
                'author' => 'Shakib Bin Kabir',
                'published_at' => '2026-03-12 00:00:00',
            ],
            [
                'slug' => 'ai-native-by-default',
                'title' => 'AI-native by default: the new minimum for internal tools',
                'excerpt' => 'Bolting AI onto a legacy admin panel is theatre. Designing for AI from line one is leverage. Here’s the shape of the shift.',
                'body' => '<h2>The shift</h2><p>Two years ago, AI was a feature you bolted on. Today it’s the substrate. The interesting question isn’t "should we add AI" — it’s "what does this tool look like if AI is assumed to exist from line one?"</p><h2>What changes</h2><p>Forms become conversations. Reports become questions. Dashboards become anomaly summaries. Onboarding becomes example-driven instead of documentation-driven. Each shift is small. Together they collapse hours.</p><h2>What stays the same</h2><p>The discipline. AI doesn’t excuse bad architecture — it amplifies it. A clean data model is the difference between AI that compounds and AI that hallucinates.</p>',
                'tags' => ['ai', 'automation'],
                'author' => 'Shakib Bin Kabir',
                'published_at' => '2026-02-26 00:00:00',
            ],
            [
                'slug' => 'the-leverage-check',
                'title' => 'The Leverage Check: three questions before we ship anything',
                'excerpt' => 'Every feature we build passes three gates: ROI, Scale, Complexity. Fail any one and we delete the ticket. Here’s why.',
                'body' => '<h2>Three gates</h2><p>Every feature we build has to clear three gates before it ships:</p><p><strong>ROI:</strong> does this make money or save time? If neither, the answer is no.</p><p><strong>Scale:</strong> does this still work at 10x the volume? If not, we’re shipping technical debt with a smile.</p><p><strong>Complexity:</strong> can we build a simpler version that gets 80% of the value? If yes, that’s the version we ship.</p><h2>Why this works</h2><p>Most product roadmaps drift because every stakeholder has reasonable-sounding asks and there’s no shared filter. Three gates are a shared filter. The conversation stops being "your idea vs. mine" and starts being "does this clear ROI, Scale, and Complexity."</p>',
                'tags' => ['process', 'engineering'],
                'author' => 'Shadman Sakib',
                'published_at' => '2026-02-12 00:00:00',
            ],
        ];

        foreach ($posts as $data) {
            BlogPost::updateOrCreate(
                ['site_id' => $site->id, 'slug' => $data['slug']],
                array_merge($data, [
                    'site_id' => $site->id,
                    'is_published' => true,
                ])
            );
        }
    }
}
