# 🛡️ AgentShield AI Defense

**AgentShield AI Defense** is an advanced WordPress security plugin designed to protect your site from aggressive AI scrapers, content theft, and indirect prompt injection attacks.

---

## 🚀 Features

### 🚫 Advanced Bot Blocking
Detects and immediately blocks known AI crawlers (GPTBot, Claude-Web, CCBot, anthropic-ai, and more) at the User-Agent level with a 403 response.

### ⚖️ IP-Based Rate Limiting
Catches bots that disguise their identity by analyzing request frequency at the IP level. Uses a dedicated database table with a configurable limit and time window. Returns a 429 Too Many Requests response when the threshold is exceeded.

### 🪤 Smart Tarpit (Honeypot)
Injects an invisible link into every page that is hidden from human visitors but enticing to AI crawlers. When a bot follows the link, it is trapped in a slow-drip response that streams dummy data with deliberate delays — wasting the bot's time and resources.

### 🧼 Content Cleaner
Strips invisible Unicode characters (zero-width spaces, soft hyphens, word joiners, etc.) embedded in post content and comments. These characters are commonly used in indirect prompt injection attacks to manipulate AI language models that process your content.

### 🆔 Dynamic Content Watermark
Appends a hidden, CSS-obfuscated digital signature to every piece of content. Unlike simple `display:none` spans, the watermark class name is derived from a hash of your site URL, making it harder to strip in bulk. Each page gets a unique, stable SHA-256 content ID based on the post ID — not a random value — so scraped content can be reliably traced back to its source.

### 📊 Live Statistics Dashboard
Monitor blocked and trapped bots in real time from the WordPress admin panel. Includes a one-click stats reset.

---

## 🛠️ Technical Details

| Feature | Implementation |
|---|---|
| Tarpit hook | `template_redirect` — fires after WordPress is fully loaded, avoiding header-conflict issues |
| Watermark obfuscation | CSS class names hashed per site (`as-{md5_prefix}`) to prevent bulk scraping/stripping |
| Rate limiting storage | `dbDelta`-managed custom table (`wp_agentshield_rate_limit`) |
| Real IP detection | Checks `HTTP_CF_CONNECTING_IP` → `HTTP_X_FORWARDED_FOR` → `HTTP_X_REAL_IP` → `REMOTE_ADDR` (Cloudflare & proxy-aware) |
| Admin protection | All blocking hooks skip admin panel requests via `is_admin()` guards |
| Content ID | `sha256(site_url + post_id + version)` — stable and traceable across scraping events |

---

## 📦 Installation

1. Download or `git clone` this repository.
2. Copy the `agentshield-ai-defense` folder to your site's `/wp-content/plugins/` directory.
3. Activate the plugin from the **Plugins** menu in your WordPress admin panel.
4. Go to **AgentShield AI** in the admin sidebar to configure your settings.

---

## ⚙️ Configuration

The plugin ships with sensible defaults and is ready to use immediately after activation. You can customize the following:

| Setting | Description | Default |
|---|---|---|
| Content Cleaner | Strip invisible Unicode characters | Enabled |
| AI Honeypot | Inject hidden trap link | Enabled |
| Content Watermark | Add hidden traceable signature | Disabled |
| Rate Limit | Enable IP-based request throttling | Enabled |
| Max Requests | Allowed requests per time window | 30 |
| Time Window | Duration of the rate limit window (seconds) | 60 |
| Trap Slug | URL path for the bot tarpit | `ai-trap-zone` |
| Bot Block List | Comma-separated User-Agent strings to block | GPTBot, CCBot, ChatGPT-User, anthropic-ai, Claude-Web, Googlebot-Extended |

---

## 📋 Changelog

### v1.2.0
- **New**: IP-based rate limiting with dedicated database table, configurable threshold and time window
- **New**: Stats reset button in admin panel
- **Improved**: Watermark now uses a stable `sha256(site_url + post_id)` content ID instead of `uniqid()` — reliably traceable across scraping events
- **Improved**: Tarpit routing moved from `init` to `template_redirect` for header-safe execution
- **Improved**: Bot blocking now skips admin panel requests (`is_admin()` guard added)
- **Improved**: Real IP detection supports Cloudflare and reverse proxies (`CF-Connecting-IP`, `X-Forwarded-For`)
- **Improved**: Content cleaner now strips additional invisible characters (`U+00AD`, `U+2060`)
- **Fixed**: Bot block list entries are now trimmed of whitespace on save

---

## 🤝 Contributing

Bug reports, feature suggestions, and pull requests are welcome. For significant changes, please open an issue first to discuss what you'd like to change.

---

**Author:** [akturks](https://github.com/akturks)
**Website:** (https://agentshieldsecurity.lemonsqueezy.com/)
**License:** GPLv2 or later
