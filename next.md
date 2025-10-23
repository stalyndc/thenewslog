With the admin workflow smoothed out, I’d line up one of these next:

Edition scheduling – add draft/scheduled/published states plus a “go live at” picker so you can stage content and let cron flip editions automatically.
Feed health dashboard – surface fetch success/failed counts, last run timestamps, and maybe lightweight alerts so you spot stale feeds fast.
Onboarding/ops checklist – document cron jobs, deploy verification, seeding steps, and environment variables to keep prod + staging in sync.
Pick whichever fits your priorities (timed publishing, observability, or ops readiness) and we can dive in.

TO DO:
FEATURES TO ADD/REMOVE

   Add:

   1. Independent Blog Posts – Allow editors to create standalone articles (not just
       curated links)
   2. Collections/Series – Group related links by theme or week
   3. Reading Time Estimation – Show "5 min read" for both posts and links
   4. Reader Accounts & Bookmarking – Let readers save favorite links/posts (future
      phase)
   5. Search – Full-text search across links and posts
   6. Feed Recommendations – "Related items" sidebar based on tags
   7. Scheduled Publishing – Queue posts/editions for future publish
   8. Draft/Review Workflow – Let editors draft before publishing
   9. Analytics Dashboard – Track link clicks, popular tags, subscriber growth
   10. Social Sharing – Pre-formatted OG meta, Twitter cards, Mastodon support

UI/DESIGN/MOBILE ENHANCEMENTS

   Current Issues:
   •  Mobile drawer is good, but nav needs active state styling with glow effect
   •  Story spacing is too loose; needs visual dividers between links
   •  "Read source" link hierarchy is unclear

   Recommendations:

   1. Tighter Card Layout – Reduce top/bottom padding 20–30px; use 1px dividers
      between stories
   2. Better Hover Feedback – Title color shift + subtle lift effect on link titles
   3. Updated Timestamp – Show "Updated X min ago" on edition header
   4. Tag Pills – Better visual distinction with background color + hover effects
   5. Mobile Touch Targets – Ensure 44px+ min height for all tap areas
   6. Dark Mode is Good – Keep your current dark palette (teal accent works well)
   7. Typography Hierarchy – Your 18px body + 28–34px headlines are solid
   8. Breadcrumb Navigation – Add on tag/edition pages for context
   9. Loading States – Skeleton loaders for inbox/edition pages in admin
   10. Footer Optimization – Add "About • Subscribe • RSS • Contact" inline links

   ──────────────────────────────────────────

   IS TWIG GOOD ENOUGH?

   Yes, Twig is excellent for your use case, but consider adding:

   1. HTMX Enhancement – You already have it! Use for:
     •  Infinite scroll on editions archive
     •  In-admin form validation + autocomplete for tags
     •  Real-time inbox updates
     •  Drag-to-reorder edition links

   2. Alpine.js – You don't have it yet, but add for lightweight interactivity:
     •  Mobile drawer toggle (instead of vanilla JS)
     •  Tooltip/popover for tag descriptions
     •  Form state management (draft saving)
     •  Keyboard shortcuts in admin

   3. Component Library Alternative? – No, stick with Twig. Adding Vue/React is
      overkill for a curated news site. Twig + HTMX + Alpine is the sweet spot.

   4. Template Inheritance – You're already using it well (layout.twig). Continue
      this pattern.

   ──────────────────────────────────────────

   BEST APPROACH FOR AI INTEGRATION (Future)

   Staged approach:

   Phase 1 (Current) – AI flag stub (already in .env as AI_SUMMARY_ENABLED)

   Phase 2 (Near-term):
   •  Generate 1-sentence summary for curated links (optional)
   •  Suggest tags automatically from article title + summary
   •  Implementation: Use OpenAI API or Claude API, cache results

   php
     // app/Services/AiSummarizer.php
     class AiSummarizer {
         public function summarize(string $url, string $title): string {
             // Call OpenAI API or Claude
             // Cache result for 30 days
             // Fallback to first sentence of article
         }

         public function suggestTags(string $title, string $summary): array {
             // Return 3–5 tags
         }
     }

   Phase 3 (Later):
   •  AI-powered newsletter intro: "Here's what stood out this week…"
   •  Trending topic detection
   •  Duplicate link detection (smarter than URL hash)
   •  Duplicate title detection (when multiple sources report same story)

   Architecture:
   •  Use a queue system (background jobs) for AI calls (don't block the request)
   •  Store AI output in DB so you can regenerate manually
   •  Always show human-editable version after AI suggestion
   •  Add cost tracking (log API spend)

   ──────────────────────────────────────────

   BEST APPROACH: INDEPENDENT BLOG POSTS (Besides Curated Links)

   Design a parallel system:

   sql
     -- New table: independent_posts
     CREATE TABLE independent_posts (
         id INT PRIMARY KEY AUTO_INCREMENT,
         title VARCHAR(280),
         slug VARCHAR(280) UNIQUE,
         content TEXT,          -- markdown or HTML (use HTMLPurifier)
         excerpt VARCHAR(500),  -- for previews
         author VARCHAR(100),
         tags_csv VARCHAR(255),
         featured_image_url TEXT,
         is_published TINYINT,
         published_at DATETIME,
         created_at DATETIME,
         updated_at DATETIME
     );

     -- Link daily editions to independent posts too:
     ALTER TABLE editions ADD COLUMN intro TEXT;
     -- And modify curated_links to be flexible:
     ALTER TABLE curated_links
     ADD COLUMN post_id INT NULLABLE,
     ADD COLUMN is_independent_post TINYINT DEFAULT 0;

   Workflow:

   1. Editor creates post in /admin/compose (rich text editor)
   2. Posts can be included in daily editions OR published standalone
   3. Home page shows mix of "Today's Picks" + "Featured Post"
   4. Archive page shows all items (posts + curated links mixed, chronological)

   ──────────────────────────────────────────

   DO YOU NEED A TEXT EDITOR? YES. BEST OPTIONS:

   Recommendation: TinyMCE Community (Open Source)
   •  Lightweight, widely used, great UX
   •  CDN-hosted (don't bloat your vendor/)
   •  Built-in table support, link toolbar, image upload hooks

   Alternative 1: Quill (Modern, Lightweight)
   •  Delta format (robust for storage)
   •  Small footprint
   •  Good mobile support

   Alternative 2: Editor.js (Block-based, Trendy)
   •  Intuitive for writers
   •  Clean JSON output
   •  Growing ecosystem

   Alternative 3: Markdown + SimpleMDE
   •  If you want to store markdown instead of HTML
   •  Simpler, more portable than rich HTML

   Setup for TinyMCE:

   html
     <!-- In your admin form -->
     <textarea name="content" id="editor"></textarea>

     <script
     src="https://cdn.tiny.cloud/1/[YOUR_KEY]/tinymce/7/tinymce.min.js"></script>
     <script>
     tinymce.init({
       selector: '#editor',
       plugins: 'link image lists code table',
       toolbar: 'bold italic | link image | bullist numlist | code table',
       images_upload_handler: myCustomUploadHandler // POST to /admin/upload-image
     });
     </script>

   Backend:

   php
     // app/Services/ImageUploader.php
     public function uploadFromTinyMce($file): string {
         $path = "storage/uploads/" . date('Y-m') . "/";
         $filename = bin2hex(random_bytes(16)) . ".jpg";
         move_uploaded_file($file, $path . $filename);
         return "/storage/uploads/" . date('Y-m') . "/" . $filename;
     }

   ──────────────────────────────────────────

   SUMMARY OF RECOMMENDATIONS (Prioritized)

   Quick Wins (1–2 days):
   [ ] Tighten card spacing + add story dividers
   [ ] Add active nav state glow
   [ ] Add "Updated X min ago" to edition header
   [ ] Better hover feedback on titles
   [ ] Improve mobile touch targets

   Medium (3–5 days):
   [ ] Add Alpine.js for lightweight interactivity
   [ ] Integrate TinyMCE for rich text editing
   [ ] Create independent_posts table + /admin/compose form
   [ ] Add search functionality (full-text MySQL index)
   [ ] Add draft/review workflow

   Future (1–2 weeks):
   [ ] Add AI summarization service (Phase 2)
   [ ] Reader accounts + bookmarking
   [ ] Collections/Series feature
   [ ] Analytics dashboard
   [ ] Background job queue (for AI calls, email sends)
