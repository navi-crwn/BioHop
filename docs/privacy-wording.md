# Privacy policy wording for sign-up forms and webhooks (proposal, needs the owner's decision)

BioHop does not decide what a page owner does with the details a visitor types. The policy should say who is responsible and what the software itself keeps.

## Proposed text for the Terms/Privacy page

**Sign-up forms on a bio page.** A page owner can add a form that asks a visitor for a name, an e-mail address and a phone number. The visitor must tick a consent box that names the page owner. The details are stored for the page owner only, together with a one-way hash of the visitor's address to limit abuse. The page owner is the controller of that data and is responsible for using it lawfully; HEL.ink acts as the processor and does not read, sell or share it. The page owner can set how long entries are kept (they are deleted automatically afterwards), export them as CSV and delete them at any time. Entries are deleted with the page or the account.

**Webhooks.** If a page owner adds a webhook address, each new form entry is also sent there as signed JSON, so the page owner's own system receives it. The page owner chooses the address and is responsible for the system behind it. HEL.ink keeps a delivery log for seven days (time, result, and the JSON that was sent, so a failed delivery can be retried or resent); it is deleted automatically after that.

**What a visitor sees.** The form shows the consent sentence next to the box: "I agree that <page name> keeps these details to get back to me."

## Check before publishing
- Delivery log retention: `privacy:prune` now deletes deliveries after seven days (it runs on the existing schedule).
- The hash of the visitor address: confirm that "one-way hash" is accurate for the legal team (HMAC with the app key).
