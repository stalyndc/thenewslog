TheNewsLog.org — Comprehensive Code Review

 Executive Summary

 Overall, the codebase is well-structured with good separation of concerns,
 but has critical security vulnerabilities that need immediate attention,
 plus several opportunities for enhancement.

 ──────────────────────────────────────────

 🔴 CRITICAL SECURITY ISSUES (Immediate Action Required)

 1. RESOLVED — Admin Auth Enforcement

 Status: Fixed. Admin access is enforced in `App\Controllers\Admin\AdminController`
 (constructor check + redirects). The unused `app/Middleware/AuthMiddleware.php`
 has been removed to avoid confusion.

 2. RESOLVED — Session Security Hardening

 Status: Fixed. `index.php` enables strict session flags before start
 (`session.use_strict_mode`/`session.use_only_cookies`) and uses secure
 cookie options with SameSite. Security headers were added globally (CSP
 with nonces, HSTS on HTTPS, Referrer‑Policy, COOP/CORP, Permissions‑Policy).
 Note: SameSite is set to Lax for admin UX; consider Strict only if it
 does not impact flows.

 3. **SQL Injection via sprintf in ItemRepository**

 File: app/Repositories/ItemRepository.php (lines 31-32, 59-60)
 Severity: HIGH
 Issue: Using sprintf() to inject WHERE clause into SQL, though parameters
 are still bound.

 php
   $sql = sprintf($sql, $where); // Then uses prepared statements

 Risk: While parameters ARE bound via PDO (good!), the pattern is dangerous
 and could be misused in future edits.
 Fix: Use query builder pattern or ensure team understands this is safe only
 because $where is hardcoded, not user input.

 4. RESOLVED — Rate Limiter Storage

 Status: Fixed. Rate limiter now stores attempt data as JSON and reads
 JSON (with legacy unserialize support for older files). Types are
 normalized and files are written with LOCK_EX.

 ──────────────────────────────────────────

 🟡 HIGH PRIORITY SECURITY ENHANCEMENTS

 5. **CSRF Token Not Validated on GET Requests**

 Observation: CSRF is only checked on POST/mutating actions (good), but tag
 suggestions/validation use GET with XHR.
 Risk: Low for current implementation, but if admin makes state-changing GET
 requests, they'd be vulnerable.
 Status: ✅ Current implementation is correct, but document this pattern.

 6. **No Rate Limiting on Tag Endpoints**

 Files: app/Controllers/Admin/TagController.php
 Issue: Tag suggest/validate endpoints have no rate limiting, allowing abuse.
 Impact: DoS via spam requests to /admin/tags/suggest.
 Fix: Apply rate limiting to AJAX endpoints.

 7. **Database Password in Plain ENV**

 File: .env
 Severity: MEDIUM
 Issue: Database credentials stored in plain text (standard practice but
 risky on shared hosting).
 Recommendation:
 •  Ensure .env has restrictive permissions (chmod 600 .env)
 •  Never commit .env to git (already in .gitignore)
 •  Consider using Hostinger's environment variable manager instead of
    file-based .env

 8. **Error Messages Leak Information**

 File: app/Bootstrap/App.php (line 214)
 Issue: PDO connection errors expose full exception messages.
 Fix: In production, log detailed errors but show generic message to user.

 9. **No Protection Against Timing Attacks in Login**

 File: app/Services/Auth.php (line 70-71)
 Issue: Email comparison uses strcasecmp() instead of timing-safe comparison.

 php
   $emailMatches = strcasecmp(trim($email), $configuredEmail) === 0;

 Fix: Use hash_equals() after normalizing both emails.

 ──────────────────────────────────────────

 🟢 BUGS & LOGICAL ISSUES

 10. **RSS Feed GUID May Not Be Unique**

 File: app/Controllers/RssController.php (line 55)
 Issue: Falls back to /curated/{id} which isn't a valid route.

 php
   $guid = $linkRow['url'] ?? ($this->baseUrl() . '/curated/' . $id);

 Fix: Use actual source URL or edition permalink.

 11. **Duplicate Column Definition in Schema**

 File: scripts/migrate.sql (editions table)
 Issue:

 sql
   `date` DATE GENERATED ALWAYS AS (edition_date) STORED

 This duplicates edition_date. Either remove or document why it exists.

 12. **Mailer Service is Stub**

 File: app/Services/Mailer.php
 Issue: Empty implementation. If subscriber table is being used, this will
 fail silently.
 Action: Either implement or remove subscriber-related code.

 13. **Potential Race Condition in Edition Creation**

 File: app/Services/Curator.php + EditionRepository
 Issue: ensureForDate() might create duplicate editions under concurrent
 requests.
 Fix: Use INSERT IGNORE or INSERT ... ON DUPLICATE KEY UPDATE.

 14. **Tag Deletion Logic May Fail Silently**

 File: app/Controllers/Admin/CurateController.php (line 113)
 Issue: Orphan tag cleanup is wrapped in try-catch that swallows errors.
 Risk: Tags accumulate over time, bloating database.
 Fix: Log warning but investigate failures.

 ──────────────────────────────────────────

 📊 PERFORMANCE ISSUES

 15. Page‑Level Caching Implemented (Further Data Caching Optional)

 Status: Improved. Public HTML endpoints now use `Response::cached()` with
 sensible TTLs and the RSS feed sends cache headers. Feed fetching already
 leverages ETag/Last‑Modified. Optional: introduce Symfony Cache for
 fragment/data caching (e.g., tag counts) if needed.

 16. **Database Indexes**

 File: scripts/migrate.sql
 Observations:
 •  ✅ Good: url_hash, feed_id, published_at, status indexes exist
 •  New indexes provided:
   •  curated_links.published_at (used in ORDER BY frequently)
   •  items.created_at (inbox sorting)
 •  How to apply:
   •  MySQL 8.0+: run `scripts/upgrade_20251026_add_indexes.sql` (uses IF NOT EXISTS)
   •  Older MySQL: `php scripts/add_indexes.php` — checks information_schema and creates missing indexes only

 17. **N+1 Query Potential in Tag Display**

 File: app/Controllers/Admin/CurateController.php
 Issue: Fetching tags separately after curated link load.
 Current: Separate query for tags via tagsForCuratedLinks()
 Status: Actually handled in bulk! ✅ Review confirmed efficient
 implementation.

 18. **No Pagination on Tags Page**

 Observation: If tag list grows large, no pagination exists.
 Risk: Low priority but worth tracking.

 ──────────────────────────────────────────

 🛠️ CODE QUALITY & BEST PRACTICES

 19. **Inconsistent Error Handling**

 Observation: Mix of:
 •  Throwing exceptions (Curator::curate())
 •  Returning null (ItemRepository::find())
 •  Try-catch with fallback (CurateController)

 Recommendation: Standardize on:
 •  Repositories: return null for not-found, throw on DB errors
 •  Services: throw domain exceptions
 •  Controllers: catch and translate to HTTP responses

 20. **Missing Type Declarations**

 Examples:
 •  Router::$notFoundHandler is mixed (could be callable)
 •  Several methods missing return types

 Fix: Enable strict_types=1 project-wide (already done in most files ✅).

 21. **Logging Levels Inconsistent**

 File: Various controllers
 Issue: Some errors logged as 'warning', others as 'error' without clear
 policy.
 Fix: Establish logging guidelines:
 •  ERROR: System failures, uncaught exceptions
 •  WARNING: Recoverable issues (missing items, feed failures)
 •  INFO: Normal operations (feed updates, curations)

 22. **Frontend: Global State Pollution**

 File: resources/ts/app.ts
 Issue: Multiple global flags like __editionInfiniteBound,
 __tagSuggestionHandler.
 Risk: Low, but cleaner to use data attributes or module state.

 23. **HTMX Integration Lacks Error Handling**

 Observation: HTMX swaps happen but no global error handler for 4xx/5xx
 responses.
 Recommendation: Add htmx:responseError event listener to show user-friendly
 errors.

 ──────────────────────────────────────────

 ✅ SECURITY STRENGTHS (Good Practices)

 1. ✅ CSRF Protection: Properly implemented with token validation
 2. ✅ Password Hashing: Uses password_verify() with bcrypt
 3. ✅ Prepared Statements: All queries use PDO prepared statements
 4. ✅ Rate Limiting: Login attempts are rate-limited per IP
 5. ✅ Session Regeneration: IDs regenerated on login/logout
 6. ✅ XSS Prevention: Twig auto-escaping enabled
 7. ✅ URL Normalization: Feed URLs normalized before hashing
 8. ✅ Conditional HTTP: ETag/If-Modified-Since for feed fetching
 9. ✅ Transaction Safety: Proper rollback on failures
 10. ✅ Input Validation: Tag length limits, date validation

 ──────────────────────────────────────────

 📋 RECOMMENDED PRIORITY ORDER

 Phase 1: Critical Security (Updated)

 1. RESOLVED — Admin auth enforcement (via AdminController)
 2. RESOLVED — Session hardening + global security headers
 3. RESOLVED — Add rate limiting to tag endpoints
 4. RESOLVED — Fix timing attack in email comparison
 5. Review SQL sprintf pattern safety (keep pattern constrained)

 Phase 2: High Priority Enhancements
 6. Implement caching layer (Symfony Cache)
 7. Add monitoring/alerting for feed failures
 8. Complete or remove Mailer implementation
 9. RESOLVED — Fix RSS GUID generation
 10. Add error boundaries to frontend

 Phase 3: Quality Improvements
 11. Standardize error handling patterns
 12. Add database indexes for curated_links.published_at
 13. Add HTMX error handling
 14. Document admin authentication flow
 15. Add automated security tests (CSRF, XSS, SQL injection)

 Phase 4: Nice-to-Haves
 16. Add tag pagination
 17. Refactor global state in TypeScript
 18. Add performance monitoring
 19. Consider query result caching
 20. Add API documentation

 ──────────────────────────────────────────

 🎯 OVERALL ASSESSMENT

 Grade: A− (Good foundation; earlier critical gaps addressed)

 Strengths:
 •  Clean architecture with proper separation
 •  Good use of prepared statements and CSRF protection
 •  Solid feed ingestion with conditional requests
 •  Type-safe TypeScript with modern patterns

 Critical Gaps (updated):
 •  Rate limiting for certain admin AJAX endpoints (tags)
 •  Timing‑safe email comparison in Auth
 •  RSS GUID fallback should use a canonical URL
 •  Some error handling inconsistencies

 Recommendation: Address Phase 1 items before production deployment. The
 codebase is production-ready after fixing the AuthMiddleware and session
 security issues.

### OTHER ###

lpine.js Enhancements & Frontend Stack Recommendations
 Current Stack Analysis

 What You Have:

 •  ✅ HTMX 1.9.12 (CDN) - Server-driven interactions
 •  ✅ Alpine.js 3.x (CDN) - Lightweight reactivity (partial usage)
 •  ✅ TypeScript - Custom modules (app.ts, reorder.ts, toast.ts,
    timeago.ts)
 •  ✅ Vite - Build tool for TS compilation
 •  ⚠️ Alpine.js Usage: Only used in 2 places:
   •  layout.twig - Mobile drawer + confirmation modals
   •  curate.twig - Tags field management
 Gap: You're underutilizing Alpine.js! Many TypeScript modules could be
 simplified with Alpine components.

 ──────────────────────────────────────────

 🎯 Part 1: Alpine.js Enhancements (Recommended)

 1. **Replace Custom TypeScript with Alpine Components**

 A. **Toast System → Alpine Component**

 Current: Custom toast.ts with imperative API
 Better: Alpine-powered toast stack

 html
   <div x-data="toastStack()" @toast.window="add($event.detail)">
     <template x-for="toast in toasts" :key="toast.id">
       <div class="toast"
            :class="`toast--${toast.variant}`"
            x-show="toast.visible"
            x-transition
            x-init="setTimeout(() => toast.visible = false,
   toast.timeout)">
         <span x-text="toast.message"></span>
         <button x-show="toast.action"
                 @click="toast.action?.fn(); remove(toast.id)">
           <span x-text="toast.action?.label"></span>
         </button>
       </div>
     </template>
   </div>

   <script>
   window.toastStack = () => ({
     toasts: [],
     nextId: 0,
     add(config) {
       this.toasts.push({
         id: this.nextId++,
         message: config.message,
         variant: config.variant || 'info',
         timeout: config.timeout || 6000,
         visible: true,
         action: config.action
       });
     },
     remove(id) {
       const idx = this.toasts.findIndex(t => t.id === id);
       if (idx >= 0) this.toasts.splice(idx, 1);
     }
   });

   // Emit from anywhere:
   window.dispatchEvent(new CustomEvent('toast', {
     detail: { message: 'Saved!', variant: 'success' }
   }));
   </script>

 Benefits:
 •  No DOM manipulation code
 •  Reactive state management
 •  Easier testing

 ──────────────────────────────────────────

 B. **Time Ago → Alpine Directive**

 Current: Custom timeago.ts with manual DOM updates
 Better: Alpine reactive binding

 html
   <time x-data="{ time: '{{ item.created_at }}' }"
         x-text="$timeAgo(time)"
         x-init="setInterval(() => $el.textContent = $timeAgo(time),
   60000)">
   </time>

   <script>
   // Alpine magic helper
   document.addEventListener('alpine:init', () => {
     Alpine.magic('timeAgo', () => (iso) => {
       const d = new Date(iso);
       const diff = Date.now() - d.getTime();
       const mins = Math.floor(diff / 60000);
       const hrs = Math.floor(mins / 60);
       const days = Math.floor(hrs / 24);

       if (mins < 60) return `${mins || 1} min ago`;
       if (hrs < 24) return `${hrs} hr${hrs > 1 ? 's' : ''} ago`;
       return `${days} day${days > 1 ? 's' : ''} ago`;
     });
   });
   </script>

 ──────────────────────────────────────────

 C. **Inbox Polling → Alpine Store**

 Current: Multiple event listeners in app.ts
 Better: Centralized Alpine store

 html
        x-init="$store.inbox.startPolling()"
        x-effect="$store.inbox.count > 0 && updateBadge()">
     <a href="/admin/inbox" x-text="`Inbox (${$store.inbox.count})`"></a>
   </div>

   <script>
   document.addEventListener('alpine:init', () => {
     Alpine.store('inbox', {
       count: {{ admin_metrics.inbox_count|default(0) }},
       latestId: {{ latest_id|default(0) }},
       polling: null,

       startPolling() {
         this.polling = setInterval(() => this.checkNew(), 45000);
       },

       async checkNew() {
         const res = await
   fetch(`/admin/inbox/poll?after_id=${this.latestId}`);
         const data = await res.json();
         if (data.count > this.count) {
           window.dispatchEvent(new CustomEvent('toast', {
             detail: { message: `${data.count - this.count} new items`,
   variant: 'info' }
           }));
         }
         this.count = data.count;
         this.latestId = data.latest_id;
       }
     });
   });
   </script>

 ──────────────────────────────────────────

 D. **Drag & Drop Reorder → Alpine Plugin**

 Current: 200+ lines of custom TypeScript
 Keep or Replace?: Your current implementation is solid, but consider
 Sortable.js (see below).

 ──────────────────────────────────────────

 2. **New Alpine.js Features to Add**

 A. **Keyboard Shortcuts (Admin Panel)**

 html
               @keydown.window.ctrl.k.prevent="openQuickSearch()">
     <!-- Admin content -->
   </div>

 B. **Persistent Settings (Dark Mode, View Preferences)**

 html
     theme: Alpine.$persist('light').as('user-theme'),
     compact: Alpine.$persist(false).as('inbox-compact')
   }" :class="theme">
     <button @click="theme = theme === 'light' ? 'dark' : 'light'">
       Toggle Theme
     </button>
   </div>

 Requires: Alpine Persist Plugin (see recommendations below)

 C. **Infinite Scroll (Editions Archive)**

 html
     <div id="edition-list">ll('/editions/partial', 1)">
       <!-- Server-rendered items -->
     </div>
     <div x-intersect="loadMore()" class="sentinel"></div>
     <div x-show="loading">Loading...</div>
   </div>

   <script>
   window.infiniteScroll = (url, startPage) => ({
     page: startPage,
     loading: false,
     hasMore: true,

     async loadMore() {
       if (this.loading || !this.hasMore) return;
       this.loading = true;
       this.page++;

       const res = await fetch(`${url}?page=${this.page}`);
       const html = await res.text();

       if (html.trim() === '') {
         this.hasMore = false;
       } else {
         document.getElementById('edition-list').insertAdjacentHTML('befo
   reend', html);

       }

       this.loading = false;
     }
   });
   </script>

 D. **Form Auto-Save (Draft Curations)**

 html
     <input x-model="formData.title" @input="changed = true">, 3000)">
     <textarea x-model="formData.blurb" @input="changed =
   true"></textarea>
     <span x-show="saving">Saving...</span>
     <span x-show="!saving && changed">Unsaved changes</span>
   </form>

 ──────────────────────────────────────────

 📦 Part 2: Recommended Libraries (Lightweight & PHP-Friendly)

 **Essential Additions**

 1. **Alpine.js Official Plugins** ⭐⭐⭐⭐⭐

 Install: CDN or npm

 html
   <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/persist@3.x.
   x/dist/cdn.min.js"></script>

   <!-- Alpine Intersect (scroll detection) -->
   <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/intersect@3.
   x.x/dist/cdn.min.js"></script>

   <!-- Alpine Focus (focus management) -->
   <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/focus@3.x.x/
   dist/cdn.min.js"></script>

   <!-- Alpine Mask (input formatting) -->
   <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/mask@3.x.x/d
   ist/cdn.min.js"></script>

 Use Cases:

 •  Persist: User theme, admin preferences, last viewed edition
 •  Intersect: Infinite scroll, lazy loading images
 •  Focus: Modal/drawer focus trapping (accessibility)
    Mask: Date input formatting, tag separators
 Priority: HIGH (Persist + Intersect)

 ──────────────────────────────────────────

 2. **Sortable.js** ⭐⭐⭐⭐⭐

 Why: Best drag-and-drop library, touch-friendly, accessible
 Size: 45KB minified
 Install:

 bash
   npm install sortablejs
 Usage (Replace your reorder.ts):

 html
     <li data-id="123">Item 1</li>-init="initSortable($el)">
     <li data-id="456">Item 2</li>
   </ul>

   <script>
   import Sortable from 'sortablejs';

   window.editionReorder = () => ({
     sortable: null,

     initSortable(el) {
       this.sortable = Sortable.create(el, {
         animation: 150,
         handle: '.drag-handle',
         onEnd: (evt) => this.saveOrder(evt)
       });
     },

     async saveOrder(evt) {
       const order = this.sortable.toArray();
       await htmx.ajax('POST', '/admin/edition/reorder', {
         values: { positions: order }
       });
     }
   });
   </script>

 Benefits:
 •  Touch/mobile support (your current code lacks this)
 •  Accessibility attributes
 •  Multi-list dragging
 •  Ghost element customization

 Priority: MEDIUM (your current implementation works, but this is better)

 ──────────────────────────────────────────

 3. **Day.js** ⭐⭐⭐⭐

 Why: 2KB date library (Moment.js replacement)
 Use Case: Better date formatting than custom timeago

 bash
   npm install dayjs

 js
   import relativeTime from 'dayjs/plugin/relativeTime';

   dayjs.extend(relativeTime);

   Alpine.magic('timeAgo', () => (iso) => dayjs(iso).fromNow());
   // Output: "2 hours ago", "3 days ago", etc.

 Priority: LOW (your timeago works fine, but this is more robust)

 ──────────────────────────────────────────

 4. **Tiptap** (Rich Text Editor) ⭐⭐⭐⭐

 Why: Modern WYSIWYG for blurbs/notes
 Size: ~100KB (modular)
 Use Case: If you want editors to format blurbs (bold, links, etc.)

 html
        x-init="initEditor($refs.editor)">}}')"
     <div x-ref="editor"></div>
     <input type="hidden" name="blurb" x-model="content">
   </div>

 Priority: LOW (you're doing plain text, which is good for consistency)

 ──────────────────────────────────────────

 5. **Choices.js** (Enhanced Select) ⭐⭐⭐⭐

 Why: Better multi-select for tags
 Size: 45KB
 Alternative to: Your custom tags field

 html
     removeItemButton: true,-init="new Choices($el, {
     duplicateItemsAllowed: false
   })">
     <option value="AI">AI</option>
     <option value="Startups">Startups</option>
   </select>

 Priority: LOW (your current tags implementation with Alpine is already
 good)

 ──────────────────────────────────────────

 6. **Fuse.js** (Fuzzy Search) ⭐⭐⭐⭐

 Why: Client-side search for tags, feeds, past editions
 Size: 18KB
 Use Case: Quick filter in admin panel

 html
     <input x-model="query" @input.debounce="search()"
   placeholder="Search feeds...">
     <template x-for="feed in results" :key="feed.id">
       <li x-text="feed.title"></li>
     </template>
   </div>

   <script>
   import Fuse from 'fuse.js';

   window.fuzzySearch = (items) => ({
     query: '',
     results: items,
     fuse: null,

     init() {
       this.fuse = new Fuse(items, { keys: ['title', 'site_url'] });
     },

     search() {
       this.results = this.query
         ? this.fuse.search(this.query).map(r => r.item)
         : items;
     }
   });
   </script>

 Priority: MEDIUM (useful for admin UX with 50+ feeds)

 ──────────────────────────────────────────

 7. **hotkeys-js** (Keyboard Shortcuts) ⭐⭐⭐

 Why: Better than Alpine's @keydown for complex shortcuts
 Size: 4KB

 js
    mport hotkeys from 'hotkeys-js';
   hotkeys('ctrl+s', (e) => {
     e.preventDefault();
     document.querySelector('form').submit();
   });

   hotkeys('g i', () => window.location.href = '/admin/inbox'); //
   Gmail-style
   hotkeys('?', () => openShortcutsModal()); // Show help

 Priority: LOW (nice-to-have for power users)

 ──────────────────────────────────────────

 **UI/Animation Libraries**

 8. **Animate.css** ⭐⭐⭐

 Why: Pre-built CSS animations
 Size: 16KB (or cherry-pick classes)

 html
        x-transition:enter="animate__animated animate__fadeIn"
        x-transition:leave="animate__animated animate__fadeOut">
     Toast message
   </div>

 Priority: LOW (Alpine's x-transition is usually enough)

 ──────────────────────────────────────────

 9. **Motion One** ⭐⭐⭐⭐

 Why: Modern, performant animations (better than GSAP for your use case)
 Size: 5KB

 js
    mport { animate } from 'motion';
   animate('.toast', { opacity: [0, 1], y: [20, 0] }, { duration: 0.3 });

 Priority: LOW (overkill unless you want advanced animations)

 ──────────────────────────────────────────

 **Accessibility**

 10. **@accessible/disclosure** or **HeadlessUI** ⭐⭐⭐⭐

 Why: Accessible dropdown/modal patterns
 Alpine Integration: Use Alpine + ARIA patterns manually (you're already
 doing this well)

 Current Status: ✅ Your modals/drawers already have proper ARIA

 ──────────────────────────────────────────

 **Testing**

 11. **Playwright** (E2E Testing) ⭐⭐⭐⭐⭐

 Why: Test HTMX + Alpine interactions
 Priority: HIGH for production confidence

 js
   test('should save curated link', async ({ page }) => {
     await page.goto('/admin/curate/123');
     await page.fill('#title', 'Test Article');
     await page.fill('#blurb', 'This is a test blurb');
     await page.click('button[type=submit]');
     await expect(page.locator('.alert--success')).toBeVisible();
   });

 ──────────────────────────────────────────

 🎯 Recommended Implementation Priority

 Phase 1: Quick Wins (1-2 days)

 1. Add Alpine Persist plugin - User preferences (theme, view mode)
 2. Add Alpine Intersect plugin - Infinite scroll on editions
 3. Convert toast.ts → Alpine component - Simpler code
  . Add Fuse.js for feed search - Better admin UX
 Phase 2: Enhanced UX (3-5 days)

 5. Replace reorder.ts with Sortable.js - Mobile-friendly drag-drop
 6. Add Alpine Focus plugin - Better modal accessibility
 7. Create Alpine store for inbox state - Centralized admin state
  . Add keyboard shortcuts (hotkeys-js) - Power user features
 Phase 3: Advanced (Optional)

 9. Form auto-save - Draft system for curations
 10. Rich text editor (Tiptap) - If you want formatted blurbs
 1. Playwright E2E tests - Production confidence
 ──────────────────────────────────────────

 📊 Bundle Size Impact

 Current Frontend: ~150KB (HTMX + Alpine + custom TS)

 After Recommended Changes:
 •  Alpine plugins: +15KB
 •  Sortable.js: +45KB
 •  Fuse.js: +18KB
 •  Total: ~230KB (still excellent for a CMS)

 Keep in mind: HTMX and Alpine are now self‑hosted under `assets/vendor/`
 and CSP blocks external CDNs; bundle size considerations apply to local
 assets.

 ──────────────────────────────────────────

 🏗️ Architecture Recommendation

 Keep:
 •  HTMX for server-driven interactions (perfect for PHP)
 •  Alpine for client-side reactivity
 •  Twig for templating

 Avoid:
 •  React/Vue/Svelte (overkill for your use case)
 •  jQuery (Alpine replaces this)
 •  Full SPA framework (defeats HTMX benefits)

 Philosophy: "HTML over the wire" with sprinkles of JavaScript

 ──────────────────────────────────────────

 🎨 Alternative: Stay Minimal

 If you want to keep it ultra-light:

 1. Convert remaining TypeScript to Alpine components
 2. Add only Alpine Persist + Alpine Intersect (5KB total)
 3. Keep your custom reorder.ts (it works!)
 4. Stick with vanilla Alpine magic helpers

 Result: ~155KB total, zero dependencies, pure Alpine + HTMX

 ──────────────────────────────────────────

 Final Recommendation

 Best Balance for TheNewsLog:

 1. ✅ Add Alpine Persist + Intersect plugins
 2. ✅ Convert toast/timeago to Alpine components
 3. ✅ Add Fuse.js for search
 4. ⚠️ Consider Sortable.js if you expand reordering features
 5. ❌ Skip Tiptap/rich editors (keep it simple)
 6. ❌ Skip animation libraries (Alpine transitions are enough)

 This gives you:
 •  Modern, maintainable Alpine patterns
 •  Better admin UX (search, persistence)
 •  Minimal bundle size increase
 •  Room to grow without framework lock-in
