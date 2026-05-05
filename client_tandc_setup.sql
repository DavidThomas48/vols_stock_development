-- Add T&C acknowledgement and signature fields to the client table
ALTER TABLE client
    ADD COLUMN has_read_tandc  TINYINT(1)   NOT NULL DEFAULT 0   AFTER office_comments,
    ADD COLUMN tandc_signature MEDIUMTEXT   NULL     DEFAULT NULL AFTER has_read_tandc;
