-- Add T&C statement text to config table so it can be edited via the Configuration page.
-- Run once. If the row already exists (re-run), the INSERT is safely ignored.

INSERT IGNORE INTO config (`group`, `name`, `value`, `comment`)
VALUES (
    'Client Registration',
    'tandc_text',
    'I confirm that the personal information I have provided is accurate and complete. I consent to Woodend Neighbourhood House Food Bank collecting and holding this information for the purpose of providing food relief services, in accordance with the Privacy Act 1988 (Cth).',
    'Statement shown on the client registration form above the T&C checkbox and signature pad.'
);
