# TheNewsLog.org - Implementation Roadmap

**Last Updated:** October 31, 2025
**Status:** Phase 1 - Stabilization & Testing

---

## ✅ COMPLETED (No longer in TODO)

- ✅ Fix Trix editor width/layout issues
- ✅ Implement tags auto-suggest functionality
- ✅ Add regular post creation (not just RSS links)
- ✅ Fix rich text HTML preservation
- ✅ Implement word count validation (250 words max)
- ✅ Improve error logging and handling

---

## 📋 PHASE 1: Stabilization & Testing (Week 1-2)

### High Priority

- [ ] **Test complete workflow end-to-end**
  - [ ] Fetch RSS feeds via cron
  - [ ] View items in inbox
  - [ ] Curate item (edit title, blurb, tags)
  - [ ] Create standalone post
  - [ ] Publish edition
  - [ ] Verify frontend display
  - [ ] Test RSS export `/rss/daily.xml`

- [ ] **Verify cron jobs on Hostinger**
  - [ ] Check `scripts/cron_fetch.php` is running (every 30 mins recommended)
  - [ ] Check `scripts/cron_housekeep.php` is running (daily)
  - [ ] Verify logs in `storage/logs/` for errors

- [ ] **Review and fix console errors**
  - [ ] Open browser dev tools on all admin pages
  - [ ] Check for JavaScript errors
  - [ ] Verify Trix editor, tags, HTMX all working
  - [ ] Test drag-drop reordering

- [ ] **Add email alerts for cron failures**
  - [ ] Modify `scripts/cron_fetch.php` to send email on exception
  - [ ] Modify `scripts/cron_housekeep.php` to send email on failure
  - [ ] Use PHPMailer or simple `mail()` function

### Medium Priority

- [ ] **Add PHPStan static analysis**
  - [ ] Install: `composer require --dev phpstan/phpstan`
  - [ ] Create `phpstan.neon` config (level 6)
  - [ ] Run and fix issues found
  - [ ] Add to GitHub Actions CI

- [ ] **Add PHP-CS-Fixer**
  - [ ] Install: `composer require --dev friendsofphp/php-cs-fixer`
  - [ ] Create `.php-cs-fixer.php` config
  - [ ] Run and standardize code formatting

### Optional

- [ ] **Start PHPUnit tests** (optional in Phase 1)
  - [ ] Install: `composer require --dev phpunit/phpunit`
  - [ ] Write tests for `Curator` service
  - [ ] Write tests for `FeedFetcher` service
  - [ ] Write tests for `Auth` service

---

## 🎯 PHASE 2: Niche Selection & Content Strategy (Week 3)

**DECISION REQUIRED:** Choose ONE niche to focus on.

### Niche Options

**Option A: AI Tools for Builders**
- Audience: Developers, product managers, tech entrepreneurs
- Sources: Product Hunt, Hacker News, AI subreddits, AI newsletters
- Monetization: High (AI SaaS companies pay well for exposure)
- Interest level: Very high (hot topic, growing market)

**Option B: Indie SaaS Launches**
- Audience: Indie hackers, bootstrappers, solo founders
- Sources: Indie Hackers, Product Hunt, Reddit /r/SideProject, Twitter
- Monetization: Medium-High (new products need exposure)
- Interest level: High (supportive community)

**Option C: Developer Tools Weekly**
- Audience: Software developers (all levels)
- Sources: GitHub Trending, Hacker News, dev.to, Reddit /r/programming
- Monetization: Medium (dev tools, hosting, SaaS)
- Interest level: High (always relevant)

### Tasks After Niche Selection

- [ ] **Update site messaging**
  - [ ] Update homepage hero section
  - [ ] Update about page
  - [ ] Update meta description
  - [ ] Update tagline/slogan

- [ ] **Curate niche-specific RSS feeds**
  - [ ] Research 10-15 high-quality feeds for chosen niche
  - [ ] Add feeds via admin interface
  - [ ] Test feed ingestion

- [ ] **Remove generic tech RSS feeds**
  - [ ] Disable or delete irrelevant feeds
  - [ ] Keep only niche-focused sources

- [ ] **Create first curated edition**
  - [ ] Pick 3-5 stories from inbox
  - [ ] Edit titles and blurbs
  - [ ] Add relevant tags
  - [ ] Publish edition

---

## 🤖 PHASE 3: Workflow Automation (Week 4-6)

### AI Summarization Implementation

- [ ] **Research and select AI API provider**
  - [ ] Option A: OpenAI GPT-4o-mini ($0.15/1M tokens)
  - [ ] Option B: Anthropic Claude Haiku (fast, cheap)
  - [ ] Option C: OpenRouter (multi-provider)
  - [ ] Compare pricing and quality

- [ ] **Implement AI summarization service**
  - [ ] Create `app/Services/AiSummarizer.php`
  - [ ] Add API key to `.env` (e.g., `OPENAI_API_KEY`)
  - [ ] Implement `summarize(string $content): string` method
  - [ ] Add error handling and rate limiting
  - [ ] Test with sample RSS items

- [ ] **Integrate AI into inbox/curation workflow**
  - [ ] Auto-generate blurbs when item is added to inbox
  - [ ] Show AI blurb in curation interface (editable)
  - [ ] Add "Regenerate" button for new AI blurb
  - [ ] Track usage and costs

- [ ] **Add smart inbox scoring**
  - [ ] Score items by: domain authority, keywords, freshness
  - [ ] Filter inbox to show top 10 items first
  - [ ] Add "Show all" toggle
  - [ ] Save scoring preferences

### UI Simplification

- [ ] **Simplify edition creation UI**
  - [ ] Consider removing scheduled publishing (just publish immediately)
  - [ ] Simplify reordering to basic up/down buttons (or keep drag-drop if working well)
  - [ ] Remove unused fields from forms
  - [ ] Streamline the workflow

- [ ] **Reduce admin complexity**
  - [ ] Hide advanced features behind "Advanced" toggle
  - [ ] Set sensible defaults (e.g., publish_now=true)
  - [ ] Reduce clicks needed to curate

### Monitoring & Analytics

- [ ] **Add RSS feed health monitoring**
  - [ ] Track last successful fetch per feed
  - [ ] Alert when feed hasn't updated in 48+ hours
  - [ ] Alert on HTTP errors (404, 500, etc.)
  - [ ] Add `/admin/feeds/health` dashboard

- [ ] **Add basic analytics**
  - [ ] Option A: Plausible Analytics (privacy-friendly, $9/mo)
  - [ ] Option B: Umami (self-hosted, free)
  - [ ] Option C: Simple Analytics
  - [ ] Track: page views, referrers, popular stories
  - [ ] NO user tracking, NO cookies

---

## 📧 PHASE 4: Email Newsletter (Week 7-8)

### Email Service Setup

- [ ] **Select email service provider**
  - [ ] Option A: Resend ($20/mo for 50k emails, best API)
  - [ ] Option B: Mailgun (pay-as-you-go)
  - [ ] Option C: SendGrid (free up to 100 emails/day)
  - [ ] Compare deliverability and pricing

- [ ] **Implement email sending service**
  - [ ] Create `app/Services/EmailSender.php`
  - [ ] Add API credentials to `.env`
  - [ ] Implement `send(string $to, string $subject, string $html): void`
  - [ ] Test with personal email

- [ ] **Create newsletter email template**
  - [ ] Design HTML email template (responsive)
  - [ ] Include: logo, edition date, 3-5 stories, unsubscribe link
  - [ ] Test in multiple email clients (Gmail, Outlook, Apple Mail)
  - [ ] Ensure plain text fallback

- [ ] **Implement automated newsletter workflow**
  - [ ] When edition is published, send to all subscribers
  - [ ] Option: Send immediately or batch send
  - [ ] Add unsubscribe token system
  - [ ] Track opens/clicks (optional, privacy-friendly)

- [ ] **Subscriber management**
  - [ ] Add `/admin/subscribers` dashboard
  - [ ] Export subscribers to CSV
  - [ ] Manual send test emails
  - [ ] Unsubscribe handling

---

## 🚀 PHASE 5: Launch & Growth (Week 9+)

### Pre-Launch Checklist

- [ ] **Content quality check**
  - [ ] Publish 5-10 test editions
  - [ ] Verify formatting on desktop and mobile
  - [ ] Check email newsletter rendering
  - [ ] Get feedback from 2-3 beta users

- [ ] **Technical readiness**
  - [ ] Verify SSL certificate (HTTPS)
  - [ ] Test sitemap.xml generation
  - [ ] Set up 404 error page
  - [ ] Add robots.txt
  - [ ] Test RSS feed in feed readers

- [ ] **Monitoring setup**
  - [ ] Set up uptime monitoring (UptimeRobot, free)
  - [ ] Configure error email alerts
  - [ ] Set up analytics dashboard
  - [ ] Create admin checklist for daily publishing

### Publishing Schedule

- [ ] **Document publishing workflow**
  - [ ] Create `PUBLISHING.md` guide
  - [ ] Define Mon/Wed/Fri 9am schedule
  - [ ] Set reminder notifications
  - [ ] Estimate time per edition (goal: 15 mins)

- [ ] **First 4 weeks of consistent publishing**
  - [ ] Week 1: 3 editions (Mon/Wed/Fri)
  - [ ] Week 2: 3 editions
  - [ ] Week 3: 3 editions
  - [ ] Week 4: 3 editions
  - [ ] Goal: Build publishing habit

### Growth & Promotion

- [ ] **Social media strategy**
  - [ ] Create Twitter/X account for newsletter
  - [ ] Share each story with commentary (not just links)
  - [ ] Engage with niche communities
  - [ ] Post to relevant subreddits (with value, not spam)

- [ ] **SEO optimization**
  - [ ] Add OpenGraph meta tags
  - [ ] Create category/tag landing pages
  - [ ] Internal linking between related editions
  - [ ] Submit sitemap to Google Search Console

- [ ] **Email growth tactics**
  - [ ] Add signup CTA on every page
  - [ ] Create "Best of" landing page
  - [ ] Offer lead magnet (optional)
  - [ ] Cross-promote in niche communities

### Metrics & Goals

**Month 1:**
- 50+ email subscribers
- 12+ published editions
- Analytics setup complete

**Month 2:**
- 100+ email subscribers
- Consistent 3x/week publishing
- Newsletter open rate >30%

**Month 3:**
- 200+ email subscribers
- First sponsor outreach
- Refined content strategy based on data

**Month 6:**
- 1,000+ subscribers
- $500-1,000/month sponsorship revenue
- Sustainable workflow (15 mins per edition)

**Month 12:**
- 3,000+ subscribers
- $2,000-5,000/month sponsorship revenue
- Established niche authority

---

## 🧪 TESTING & QUALITY (Ongoing)

### Test Coverage (Add gradually)

- [ ] **PHPUnit - Unit Tests**
  - [ ] `CuratorTest.php` - Test curate() and createPost()
  - [ ] `FeedFetcherTest.php` - Test RSS parsing
  - [ ] `AuthTest.php` - Test login/logout
  - [ ] `ValidatorTest.php` - Test input validation
  - [ ] Target: 50%+ code coverage

- [ ] **Playwright - E2E Tests**
  - [ ] Test: Login to admin
  - [ ] Test: Curate an item from inbox
  - [ ] Test: Create standalone post
  - [ ] Test: Publish edition
  - [ ] Test: Verify frontend display
  - [ ] Run in CI on every commit

### Code Quality Tools

- [ ] **PHPStan** - Static analysis (level 6+)
- [ ] **PHP-CS-Fixer** - Code formatting
- [ ] **Rector** - Automated refactoring (optional)
- [ ] **PHPMD** - Mess detection (optional)

---

## 💰 MONETIZATION (Month 6+)

### Preparation

- [ ] **Create media kit**
  - [ ] Audience demographics
  - [ ] Open rates, click rates
  - [ ] Sponsor pricing tiers
  - [ ] Example sponsor placement

- [ ] **Identify potential sponsors**
  - [ ] List 20-30 niche-relevant companies
  - [ ] Research their marketing budgets
  - [ ] Find contact info (marketing@, partnerships@)

- [ ] **Outreach campaign**
  - [ ] Email template for sponsor pitch
  - [ ] Offer trial sponsorship at discount
  - [ ] Track responses and follow-ups

### Pricing Strategy

**Starting rates (at 2,000 subscribers):**
- Single edition sponsor: $250-500
- Weekly sponsor (3 editions): $750-1,200
- Monthly sponsor (12 editions): $2,000-3,500

**Growth rates (at 5,000 subscribers):**
- Single edition: $500-1,000
- Weekly: $1,500-2,500
- Monthly: $4,000-7,500

---

## 🔧 TECHNICAL DEBT (Low Priority)

### Infrastructure Improvements

- [ ] Add Redis caching (if Hostinger supports)
- [ ] Implement queue system (Beanstalkd or database-based)
- [ ] Set up staging environment
- [ ] Add Docker dev environment (optional)
- [ ] Implement database migrations system

### Code Improvements

- [ ] Refactor large controllers into service classes
- [ ] Add type hints to all methods
- [ ] Document complex logic with comments
- [ ] Create developer documentation
- [ ] Add API documentation (if public API added)

### Performance Optimization

- [ ] Add database indexes for slow queries
- [ ] Implement lazy loading for images
- [ ] Optimize asset bundle size
- [ ] Add HTTP/2 support
- [ ] Implement CDN for static assets (optional)

---

## 📝 Notes

**Decision Points:**
- Niche selection (Phase 2) - BLOCKS all downstream work
- AI provider (Phase 3) - Compare cost vs. quality
- Email service (Phase 4) - Prioritize deliverability

**Risk Mitigation:**
- Back up database weekly (automate via cron)
- Test on staging before deploying to production
- Monitor error logs daily
- Keep dependencies updated monthly

**Success Metrics:**
- Publishing consistency (3x/week for 12 weeks)
- Email list growth (50/month minimum)
- Time per edition (<20 mins)
- Newsletter open rate (>25%)

---

## 🎯 Current Priority: PHASE 1

**Next Action:** Test complete workflow end-to-end and verify cron jobs are working.

**Estimated Timeline:**
- Phase 1: 1-2 weeks
- Phase 2: 1 week
- Phase 3: 2-3 weeks
- Phase 4: 1-2 weeks
- Phase 5: Ongoing

**Total to MVP:** ~8 weeks to launch with email sending

---

**Remember:** The goal is sustainable, consistent publishing with minimal time investment. Don't over-engineer. Ship and iterate.
