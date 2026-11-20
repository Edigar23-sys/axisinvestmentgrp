# Axis Investment Group

The public site introduces Axis’s advisory services, seven sectors, capital network and investment pathways. The investor portal lives at `/investors` and the publishing dashboard at `/admin`.

The application is server-rendered PHP with a SQLite database. Uploaded images, logos, PDFs and media are stored in the private database. The dashboard manages projects, products, portfolio brands, and insights, including articles, blog posts, white papers, resources and media. Save a listing as a draft while you work on it, then change visibility to **Published** to make it public.

## cPanel setup

1. Use PHP 8.3 or newer with `pdo_sqlite` and `fileinfo` enabled and SQLite 3.35 or newer. Apache or LiteSpeed needs to honor the rewrite rules in `public/.htaccess`. Set PHP's `upload_max_filesize` to at least `20M` and `post_max_size` to at least `28M` for the largest uploads.
2. Set the domain's document root to the deployed `public/` directory. Keep `app/`, `bin/`, `config.php`, and `storage/` outside every web document root. If the primary domain is fixed to `public_html`, the equivalent layout places the contents of `public/` in `public_html` and places `app/`, `bin/`, and the configuration beside `public_html`. Include the hidden `.htaccess` file. Preserve any existing host-specific PHP directives when merging rewrite configuration.
3. Copy `config.example.php` to `config.php` next to the `app/` directory. Set `origin` to the final HTTPS website URL and use cPanel's HTTPS redirect. Ensure the PHP process can write to the private storage path, and that terminal commands use the same PHP version as the site. Configure PHP to log server errors with `display_errors` disabled.
4. From the directory containing `bin/`, run `php bin/admin.php admin@example.com`, substituting the administrative email. The terminal prints a generated password once. Store it in a password manager. Running the same command again for that admin address rotates the password.
5. Sign in at `/admin` and publish the first approved entries. Back up the private database and configuration with the hosting account's backup procedure. The database backup contains uploads and enquiry records, so keep it private.

[cPanel MultiPHP Manager](https://docs.cpanel.net/cpanel/software/multiphp-manager-for-cpanel/)

## Enquiries and invitations

- New individual, institutional, SACCO and advisory enquiries appear in **Enquiries**.
- Open an investor enquiry, set it to **Approved**, then generate an invitation. The dashboard shows a one-time setup link. Send it to the investor using your usual email workflow. It expires in 48 hours.
- The investor sets a password, signs in at `/investors`, and can see their linked enquiries and their status. Existing investors can submit additional enquiries from their account.
- The public **Invest Now** buttons open an enquiry form. The site accepts no payments or financial transactions.

## Local preview

From this directory, run the two commands below in separate terminal sessions. For Windows PHP distributions, enable the SQLite and file information extensions in the local PHP configuration first.

```sh
php bin/admin.php admin@example.com
php -d upload_max_filesize=20M -d post_max_size=28M -S 127.0.0.1:8127 -t public bin/router.php
```

Open `http://127.0.0.1:8127`. The default local configuration creates its own private database on first use.

## Reference sites

- The Africa focus, sector framing and portfolio treatment were informed by [Helios](https://www.heliosinvestment.com/) and [DPI](https://www.dpi-llp.com/).
- The product navigation, advisory paths and separate investor entry points drew on [J.P. Morgan Asset Management](https://am.jpmorgan.com/us/en/asset-management/adv/), [Tanmia](https://www.tanmia.om/), [MCB Capital Markets](https://mcbcapitalmarkets.mu/), [Apollo](https://www.apollo.com/), [Carlyle](https://www.carlyle.com/), and [Blackstone](https://www.blackstone.com/).
- The mix of perspectives, media and downloadable resources drew on [Harmony](https://www.harmony.co.za/investors/), [TPG](https://www.tpg.com/), [World Economic Forum](https://www.weforum.org/), [BCG](https://www.bcg.com/), and [McKinsey](https://www.mckinsey.com/).

The supplied Axis logo is used in the site header and footer. The city, SACCO, advisory, solar and port images are generated editorial illustrations. Projects, brands and investment listings start empty so Axis can add approved entries and their own photographs.

