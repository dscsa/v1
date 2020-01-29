-- Accepted Values

-- Ph. Des Moines SafenetRx:
UPDATE
	donation_items
JOIN
	donation ON donation.id = donation_id
SET
	donation_items.archived = 0,
	donee_qty = 0.5 * donor_qty
WHERE
	donor_id = 1160 AND
	donee_id = 1052 AND
	YEAR(COALESCE(donation.date_shipped, donation.date_received, donation.date_verified, donation.created)) >= 2016;

-- Westlake -> VPCP
UPDATE
	donation_items
JOIN
	donation ON donation.id = donation_id
SET
	donation_items.archived = 0,
	donee_qty = 0.35 * donor_qty
WHERE
	donor_id = 1026 AND
	donee_id = 850 AND
	YEAR(COALESCE(donation.date_shipped, donation.date_received, donation.date_verified, donation.created)) <= 2015;

UPDATE
	donation_items
JOIN
	donation ON donation.id = donation_id
SET
	donation_items.archived = 0,
	donee_qty = 0.50 * donor_qty
WHERE
	donor_id = 1026 AND
	donee_id = 850 AND
	YEAR(COALESCE(donation.date_shipped, donation.date_received, donation.date_verified, donation.created)) >= 2016;

-- Sentara  —> SafenetRx
UPDATE
	donation_items
JOIN
	donation ON donation.id = donation_id
SET
	donation_items.archived = 0,
	donee_qty = 1.0 * donor_qty
WHERE
	donor_id = 1291 AND
	donee_id = 1052

-- Worthington —> CPC
/*
UPDATE
	donation_items
JOIN
	donation ON donation.id = donation_id
SET
	donation_items.archived = 0,
	donee_qty = 0.35 * donor_qty
WHERE
	donor_id = 744 AND
	donee_id = 745 AND
	YEAR(COALESCE(donation.date_shipped, donation.date_received, donation.date_verified, donation.created)) <= 2015;

UPDATE
	donation_items
JOIN
	donation ON donation.id = donation_id
SET
	donation_items.archived = 0,
	donee_qty = 0.50 * donor_qty
WHERE
	donor_id = 744 AND
	donee_id = 745 AND
	YEAR(COALESCE(donation.date_shipped, donation.date_received, donation.date_verified, donation.created)) >= 2016;
*/
