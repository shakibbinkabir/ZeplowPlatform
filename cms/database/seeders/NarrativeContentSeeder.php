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
 * Seeds the Narrative site (narrative.zeplow.com) with the content specified
 * in Narrative_Site_PRD.md §16. Tone follows the brand document — provocative,
 * human, editorial. The voice is "The Honest Storyteller."
 *
 * WARNING: Observers are suppressed via Model::withoutEvents() so seeding
 * does not dispatch SyncContentJob for every save. After seeding, trigger
 * a manual "Resync All" from Filament (or per-site sync via tinker) so the
 * API receives the new records and Cloudflare Pages rebuilds.
 *
 * Run on the cPanel CMS host:
 *
 *   cd ~/cms.zeplow.com
 *   git pull
 *   php artisan db:seed --class=Database\\Seeders\\NarrativeContentSeeder
 *
 * Re-running is safe — every record uses updateOrCreate keyed on
 * site_id + (slug | email | name) so the seed is idempotent.
 */
class NarrativeContentSeeder extends Seeder
{
    public function run(): void
    {
        Model::withoutEvents(function () {
            $site = Site::where('key', 'narrative')->firstOrFail();

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
                'cta_text' => 'Book a Heartbeat Review',
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
                'seo_title' => 'Zeplow Narrative — Stories that sell.',
                'seo_description' => 'Brand storytelling, identity & content systems. We turn businesses into stories worth following.',
                'content' => [
                    [
                        'type' => 'hero',
                        'data' => [
                            'heading' => "We don't make ads. We make your business unforgettable.",
                            'subheading' => 'Brand storytelling, identity & content systems for founders who refuse to sound like everyone else.',
                            'cta_text' => 'Book a Heartbeat Review',
                            'cta_url' => '/contact',
                        ],
                    ],
                    [
                        'type' => 'text',
                        'data' => [
                            'heading' => 'The Invisibility Tax.',
                            'body' => '<p>Your product is great. But no one is telling the story. Every day your brand goes unnoticed, you are paying an invisible tax — in lost trust, lost customers, and lost legacy.</p><p>We do not fix that with more posts, more ads, or another rebrand. We fix it by finding the story your audience is already waiting for, and telling it the way only you can.</p>',
                        ],
                    ],
                    [
                        'type' => 'cta',
                        'data' => [
                            'heading' => "If this feels like your kind of thinking, we should talk.",
                            'description' => 'A Heartbeat Review is a deep diagnostic of your brand health — sentiment, gaps, and the story your audience is already waiting for.',
                            'button_text' => 'Book a Heartbeat Review',
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
                'seo_title' => 'About — Zeplow Narrative',
                'seo_description' => 'The Honest Storyteller. We reject transactional advertising in favour of raw, intimate narratives. Our purpose, vision, mission, and the people behind the work.',
                'content' => [
                    [
                        'type' => 'hero',
                        'data' => [
                            'heading' => 'About Zeplow Narrative',
                            'subheading' => 'The Honest Storyteller. Provocative. Human. Unapologetic.',
                        ],
                    ],
                    [
                        'type' => 'text',
                        'data' => [
                            'heading' => 'Purpose.',
                            'body' => '<p>To bridge the gap between businesses and people through the power of raw, human storytelling.</p>',
                        ],
                    ],
                    [
                        'type' => 'text',
                        'data' => [
                            'heading' => 'Vision.',
                            'body' => '<p>To turn businesses into household names by showcasing the people and passion behind the logo.</p>',
                        ],
                    ],
                    [
                        'type' => 'text',
                        'data' => [
                            'heading' => 'Mission.',
                            'body' => '<p>To help brands unlock their full potential by rejecting traditional, transactional advertising in favour of unconventional, intimate narratives.</p>',
                        ],
                    ],
                    [
                        'type' => 'text',
                        'data' => [
                            'heading' => 'Values.',
                            'body' => '<p><strong>Radical Authenticity.</strong> No fake stories. We do not polish turds. If the story is not real, we do not tell it.</p><p><strong>Intimacy over Noise.</strong> Deep connection beats loud reach. We would rather 1,000 people feel something than 100,000 scroll past.</p><p><strong>People First.</strong> Human elements over metrics. We measure success by engagement and sentiment, not vanity clicks.</p>',
                        ],
                    ],
                    [
                        'type' => 'stats',
                        'data' => [
                            'stats' => [
                                ['label' => 'Brands Served', 'number' => '20', 'suffix' => '+'],
                                ['label' => 'Campaigns Produced', 'number' => '60', 'suffix' => '+'],
                                ['label' => 'Content Pieces', 'number' => '1.2k', 'suffix' => ''],
                                ['label' => 'Avg. Engagement Lift', 'number' => '3x', 'suffix' => ''],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'services',
                'title' => 'Services',
                'template' => 'services',
                'sort_order' => 2,
                'seo_title' => 'Services — Zeplow Narrative',
                'seo_description' => 'Seven creative disciplines, one story system. We build brand perception through narrative, consistency, and execution quality.',
                'content' => [
                    [
                        'type' => 'hero',
                        'data' => [
                            'heading' => 'What We Do',
                            'subheading' => 'Seven creative disciplines. One story system.',
                        ],
                    ],
                    [
                        'type' => 'text',
                        'data' => [
                            'body' => '<p>Every service below is designed to solve a specific business bottleneck. We do not sell isolated deliverables. We build brand perception.</p>',
                        ],
                    ],
                    [
                        'type' => 'cards',
                        'data' => [
                            'heading' => 'The disciplines.',
                            'cards' => [
                                ['title' => 'Strategy & Planning · The Brain', 'description' => 'Heartbeat Review, positioning, legacy roadmap, content ecosystem design, founder personal brand architecture, GTM, ICP, competitor analysis, CX journey mapping.'],
                                ['title' => 'Creative Shoots · The Raw Material', 'description' => 'Product, campaign, documentary, OVC, UGC, lifestyle, corporate event, talking-head, drone, architectural — captured the way your brand actually feels.'],
                                ['title' => 'Video Editing · The Story', 'description' => 'Short form, long form, documentary narrative, case studies, product reels, motion graphics, 2D/3D animation, podcast editing. Cut for emotion, not just rhythm.'],
                                ['title' => 'Brand Photography · The Image', 'description' => 'Executive portraits, founder story, culture & vibes, process, food, model/lifestyle, retouching. Always human-first.'],
                                ['title' => 'Caption & Copywriting · The Voice', 'description' => 'Scriptwriting, blogs, editorial, ads copy, email sequences, website/UI copy, verbal identity guidelines. Words that sound like you.'],
                                ['title' => 'Graphics & Visuals · The Face', 'description' => 'Brand identity systems, logos, packaging & print, social posters, decks, illustrations, OOH/large format. The visual fingerprint of your brand.'],
                                ['title' => 'Management & Growth · The Engine', 'description' => 'Social media, community, reputation, influencer management, paid media, Heartbeat Report, email/newsletter, founder profile management.'],
                            ],
                        ],
                    ],
                    [
                        'type' => 'text',
                        'data' => [
                            'heading' => 'The Heartbeat Check.',
                            'body' => '<p>Every piece of work passes three gates before it ships:</p><p><strong>Strategy Check.</strong> Does this solve the specific client problem?</p><p><strong>Craft Check.</strong> Is the visual distinct and expensive-looking?</p><p><strong>Truth Check.</strong> Is it Human, or does it feel like a Corporate Ad? If it feels like a standard ad, we kill it.</p>',
                        ],
                    ],
                    [
                        'type' => 'cta',
                        'data' => [
                            'heading' => 'Start with a Heartbeat Review.',
                            'description' => 'We diagnose your brand health, map the gaps, and show you the story your audience is already waiting for.',
                            'button_text' => 'Book a Heartbeat Review',
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
                'seo_title' => 'Our Work — Zeplow Narrative',
                'seo_description' => 'Stories we have told. Brands we have transformed. Perception we have shifted.',
                'content' => [
                    [
                        'type' => 'hero',
                        'data' => [
                            'heading' => 'Our Work',
                            'subheading' => 'Every project below started with a brand that felt invisible. Here is what happened next.',
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'process',
                'title' => 'Our Process',
                'template' => 'process',
                'sort_order' => 4,
                'seo_title' => 'Our Process — Zeplow Narrative',
                'seo_description' => 'We do not guess. We diagnose. Six steps, three gates — how we consistently turn invisible brands into household names.',
                'content' => [
                    [
                        'type' => 'hero',
                        'data' => [
                            'heading' => 'Our Process',
                            'subheading' => 'We do not guess. We diagnose.',
                        ],
                    ],
                    [
                        'type' => 'text',
                        'data' => [
                            'body' => '<p>Every engagement follows the same framework. It is how we consistently turn invisible brands into household names.</p>',
                        ],
                    ],
                    [
                        'type' => 'cards',
                        'data' => [
                            'heading' => 'The six steps.',
                            'cards' => [
                                ['title' => '01 · Discovery', 'description' => 'We start by listening. What is broken? What is ambitious? What keeps you up at night? We map your brand perception before proposing anything.'],
                                ['title' => '02 · Strategy', 'description' => 'We diagnose the root cause, define the Narrative Arc, and build a roadmap with clear milestones. You see the plan before we touch a camera.'],
                                ['title' => '03 · Architecture', 'description' => 'Content pillars, visual direction, campaign structure. The blueprint for your brand story system.'],
                                ['title' => '04 · Execution', 'description' => 'We produce. You get updates, not meetings. Focused sprints — shipping real content every week. We push back when something does not serve the story.'],
                                ['title' => '05 · Delivery', 'description' => 'Brand-ready files, content systems, and launch assets. Tested, polished, ready to perform.'],
                                ['title' => '06 · Partnership', 'description' => 'Launch is not the end. We manage, optimise, and grow your brand monthly. You retain a creative team for the price of one hire. We resell our value every 30 days.'],
                            ],
                        ],
                    ],
                    [
                        'type' => 'text',
                        'data' => [
                            'heading' => 'The Heartbeat Check.',
                            'body' => '<p>Three gates before anything ships:</p><p><strong>Strategy Check.</strong> Does this solve the specific client problem?</p><p><strong>Craft Check.</strong> Is the visual distinct and expensive-looking?</p><p><strong>Truth Check.</strong> Is it Human, or does it feel like a Corporate Ad? If it feels like an ad, we kill it.</p>',
                        ],
                    ],
                    [
                        'type' => 'cta',
                        'data' => [
                            'heading' => 'Ready to find your brand\'s heartbeat?',
                            'button_text' => 'Book a Heartbeat Review',
                            'button_url' => '/contact',
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'insights',
                'title' => 'Insights',
                'template' => 'insights',
                'sort_order' => 5,
                'seo_title' => 'Insights — Zeplow Narrative',
                'seo_description' => 'Thoughts on brand storytelling, founder visibility, and making businesses unforgettable.',
                'content' => [
                    [
                        'type' => 'hero',
                        'data' => [
                            'heading' => 'Insights',
                            'subheading' => 'Thoughts on brand storytelling, founder visibility, and making businesses unforgettable.',
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'contact',
                'title' => 'Contact',
                'template' => 'contact',
                'sort_order' => 6,
                'seo_title' => 'Contact — Zeplow Narrative',
                'seo_description' => 'Book a Heartbeat Review. We will diagnose your brand health, map the gaps, and show you the story your audience is waiting for.',
                'content' => [
                    [
                        'type' => 'hero',
                        'data' => [
                            'heading' => "Let's Make Your Brand Unforgettable",
                            'subheading' => 'Book a Heartbeat Review — we will diagnose your brand health, map the gaps, and show you the story your audience is waiting for.',
                        ],
                    ],
                    [
                        'type' => 'text',
                        'data' => [
                            'body' => '<p>Reach us at <a href="mailto:hello@zeplow.com">hello@zeplow.com</a>, or send the form below. We reply within 24 hours.</p>',
                        ],
                    ],
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
                'slug' => 'karims-kitchen',
                'title' => "The Burger Joint That Became a Heartbeat",
                'one_liner' => 'Turning a local restaurant into the neighbourhood story people came back for.',
                'client_name' => "Karim's Kitchen",
                'industry' => 'Food & Beverage',
                'url' => null,
                'challenge' => "Karim's Kitchen had the best burger in the neighbourhood and no one outside three streets knew it. The menu was scattered, the brand felt like every other Instagram-bait restaurant, and the founder — Karim himself — was completely invisible in his own marketing.",
                'solution' => 'We made Karim the brand. Documentary-style founder content, kitchen-floor photography, weekly "behind the line" reels, and a verbal identity built around his own voice — sharp, warm, slightly stubborn. The food got the same treatment: real plates, real light, no styling.',
                'outcome' => 'DMs started coming in for Karim, not just the food. Wait times tripled inside three months. The brand outgrew the location — they opened a second outlet a year later because customers were asking for one in their neighbourhood.',
                'tech_stack' => [],
                'tags' => ['food', 'founder-story', 'identity'],
                'featured' => true,
                'sort_order' => 0,
            ],
            [
                'slug' => 'lakeshore-living',
                'title' => 'Unmasking Lakeshore Living',
                'one_liner' => 'Making a real estate firm feel human in a cold, transactional market.',
                'client_name' => 'Lakeshore Living',
                'industry' => 'Real Estate',
                'url' => null,
                'challenge' => 'Lakeshore Living sold premium homes to families, but their marketing sounded like a bank. Stock-image happy families, sterile drone shots, copy written by a committee. Buyers were treating them like a vendor, not a partner.',
                'solution' => "We threw out the corporate playbook. New content pillars built around the families who actually lived in their homes — kids in driveways, real kitchens, real weekend chaos. Founder-led video where the CEO talked about the streets she grew up on. Captions that sounded like a neighbour, not a brochure.",
                'outcome' => 'Inquiry quality jumped — fewer tire-kickers, more pre-qualified buyers. Sales cycle on flagship listings shortened by 40%. Recognised as the "agency that feels like home" in their region.',
                'tech_stack' => [],
                'tags' => ['real-estate', 'brand-system', 'photography'],
                'featured' => true,
                'sort_order' => 1,
            ],
            [
                'slug' => 'oksana-soap',
                'title' => 'The Founder Behind the Soap',
                'one_liner' => 'From faceless D2C brand to a founder-led movement.',
                'client_name' => 'Oksana Skincare',
                'industry' => 'Personal Care',
                'url' => null,
                'challenge' => 'Oksana had a beautifully formulated skincare line and a founder who refused to be on camera. The brand was visually polished and emotionally invisible. Conversion was fine, retention was awful — nobody felt like they belonged to anything.',
                'solution' => "We coaxed the founder out of hiding — slowly. Started with a written manifesto in her voice. Then audio. Then a short 'why I started this' film. Built an entire founder-visibility track parallel to product launches, so every drop felt like a chapter, not a sale.",
                'outcome' => "Retention doubled. The founder's personal Instagram outgrew the brand's. Customers stopped asking 'what's in this?' and started asking 'what's next?'.",
                'tech_stack' => [],
                'tags' => ['d2c', 'founder-visibility', 'personal-care'],
                'featured' => true,
                'sort_order' => 2,
            ],
            [
                'slug' => 'norse-table',
                'title' => 'A Restaurant That Forgot Its Origin',
                'one_liner' => 'Bringing a Nordic-inspired hospitality brand back to its first principle: the table.',
                'client_name' => 'Norse Table',
                'industry' => 'Hospitality',
                'url' => null,
                'challenge' => 'Norse Table had been pitched, packaged, and re-pitched into oblivion by previous agencies. The brand was technically beautiful and emotionally dead — nobody could remember why it existed.',
                'solution' => "We stripped it back. Found the founders' original obsession — long communal meals — and rebuilt the entire brand system around that one idea. Tablecloth-and-candlelight photography. Long-form captions about a single dish. No discount codes, ever.",
                'outcome' => 'Reservations filled three weeks out within the season. Brand stopped competing on price and started attracting press coverage on its own.',
                'tech_stack' => [],
                'tags' => ['hospitality', 'rebrand', 'editorial'],
                'featured' => false,
                'sort_order' => 3,
            ],
            [
                'slug' => 'mara-fitness',
                'title' => 'The Coach Who Stopped Selling Programs',
                'one_liner' => 'Turning a fitness coach into a movement people opted into willingly.',
                'client_name' => 'Mara Fitness',
                'industry' => 'Health & Fitness',
                'url' => null,
                'challenge' => "Mara was on the discount-program treadmill — $99 challenges, $19 trials, constant launches, no loyalty. Her audience saw her as a vendor selling reps, not a coach building bodies.",
                'solution' => "We killed the discount funnel. Replaced it with a Heartbeat Report: a monthly letter from Mara about training, recovery, and the messy middle. The 'product' became implicit — readers asked how to work with her.",
                'outcome' => 'Inbound DM volume went up 5x. Coaching slots filled without a single launch campaign for the rest of the year.',
                'tech_stack' => [],
                'tags' => ['fitness', 'personal-brand', 'newsletter'],
                'featured' => false,
                'sort_order' => 4,
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
        // On Narrative, Shadman is listed first — he is the delivery owner for
        // Narrative. On Logic, Shakib is first. Same humans, different order.
        $members = [
            [
                'name' => 'Shadman Sakib',
                'role' => 'Co-Founder & CEO',
                'bio' => 'Strategy, direction, and brand & venture leadership. Leads strategy, client relationships, and brand direction across the Zeplow group. Asks "why does this exist?" before anything gets built. The person who sets the course.',
                'linkedin' => 'https://linkedin.com/in/shadmansakib',
                'email' => 'shadman@zeplow.com',
                'is_founder' => true,
                'sort_order' => 0,
            ],
            [
                'name' => 'Shakib Bin Kabir',
                'role' => 'Co-Founder & CTO',
                'bio' => 'Systems, automation, AI & technical architecture. Leads technology, product development, and infrastructure decisions across the Zeplow group. The person who builds the machine.',
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

    private function seedTestimonials(Site $site): void
    {
        // PRD §16.5 — emotional, transformation-focused testimonials.
        $items = [
            [
                'name' => 'Karim Ahmed',
                'role' => 'Founder',
                'company' => "Karim's Kitchen",
                'quote' => "They didn't just rebrand my restaurant. They told the story I was too close to see. The comments aren't just about the food anymore — they're talking about us.",
                'sort_order' => 0,
            ],
            [
                'name' => 'Oksana Vinogradova',
                'role' => 'Founder',
                'company' => 'Oksana Skincare',
                'quote' => "I hated being on camera. They didn't force it — they earned it. Six months later my customers know my name before they know which product they want.",
                'sort_order' => 1,
            ],
            [
                'name' => 'Hanna Lindqvist',
                'role' => 'CEO',
                'company' => 'Lakeshore Living',
                'quote' => "Every other agency tried to make us look 'premium.' Zeplow made us look human. That's what actually closed the deals.",
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
                'slug' => 'the-invisibility-tax',
                'title' => 'The Invisibility Tax: what your brand is paying every day it goes unnoticed',
                'excerpt' => 'Your product is great. Nobody is telling the story. Here is what that costs you — in trust, in customers, in legacy.',
                'body' => '<h2>The hidden bill</h2><p>Every business with a good product and no story is paying an invisibility tax. It does not show up on any P&amp;L. It shows up in the customer who almost bought but went with the louder option. In the partnership that never started because you were not on anyone\'s radar. In the hire who took the offer from the company with the better narrative.</p><h2>Why it compounds</h2><p>The tax is not flat. It compounds. Every quarter your brand stays invisible, the cost of becoming visible later goes up — because by then your competitors have shaped the category narrative, and you are arguing into wind.</p><h2>The fix is not more</h2><p>More posts will not fix it. More ads will not fix it. Another rebrand will not fix it. What fixes it is finding the story your audience is already waiting for, and telling it the way only you can.</p>',
                'tags' => ['storytelling', 'brand-strategy'],
                'author' => 'Shadman Sakib',
                'published_at' => '2026-03-18 00:00:00',
            ],
            [
                'slug' => 'brand-autopsies',
                'title' => "Brand Autopsies: why most rebrands fail before the new logo lands",
                'excerpt' => 'A rebrand that does not start with a confession is not a rebrand. It is a cover-up. A field guide to telling the difference.',
                'body' => '<h2>The diagnosis</h2><p>Most rebrands fail for the same reason: they start with the deliverable instead of the truth. New logo, new palette, new tagline — all polished, all hollow. The audience can feel the difference between a brand that has changed and a brand that has been redecorated.</p><h2>The autopsy</h2><p>Before we touch a visual, we do a brand autopsy. What did the old brand actually promise? Where did it lie? Who did it embarrass? What were the founders too tired to say out loud? Those are the questions that produce a rebrand that sticks.</p><h2>The honest answer</h2><p>If a rebrand cannot survive a conversation that starts with "what was actually broken?", it will not survive the market either. Honest answers are the only foundation we have ever seen hold.</p>',
                'tags' => ['rebrand', 'strategy'],
                'author' => 'Shadman Sakib',
                'published_at' => '2026-03-02 00:00:00',
            ],
            [
                'slug' => 'founder-confessions',
                'title' => 'Founder Confessions: what the case studies do not say',
                'excerpt' => 'The wins are easy to write up. The middle is where the work actually happens. A short, uncomfortable list of things we do not put in the deck.',
                'body' => '<h2>The middle</h2><p>Case studies are the polished end. The middle is messy. We push back. Clients push back. Sometimes both sides are right. Sometimes nobody is. The case study never shows the week the brand strategy was scrapped two days before a shoot because the founder finally said the thing she actually meant.</p><h2>What we tell ourselves</h2><p>We tell ourselves the messy middle is the work. It is the part that earns the trust. By the time the campaign lands, the relationship has either survived three honest conversations or it has not. The campaign is downstream of those.</p><h2>What we tell clients</h2><p>If you want a vendor who never disagrees with you, we are the wrong choice. If you want a creative partner who will tell you when the story you are paying for is not actually yours, this is what that looks like in practice.</p>',
                'tags' => ['process', 'craft'],
                'author' => 'Shadman Sakib',
                'published_at' => '2026-02-18 00:00:00',
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
