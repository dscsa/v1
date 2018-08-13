<?=
	isset($message) ? $message : '',

	html::h3("Download Reports")

		->ol('', 'main_color')

			->li(html::link('admin/metrics', 'Donations.csv'))

			->li(html::link('admin/metrics/2010', 'All Items 2010.csv'))

			->li(html::link('admin/metrics/2011', 'All Items 2011.csv'))

			->li(html::link('admin/metrics/2012', 'All Items 2012.csv'))

			->li(html::link('admin/metrics/2013', 'All Items 2013.csv'))

			->li(html::link('admin/metrics/2014', 'All Items 2014.csv'))

			->li(html::link('admin/metrics/2015', 'All Items 2015.csv'))

			->li(html::link('admin/metrics/2016', 'All Items 2016.csv'))

			->li(html::link('admin/metrics/2017', 'All Items 2017.csv'))

		->end()

		->h3("Update Database")

		->add(form::open().'Do updates in order unless you know what you are doing')

		->ul('main_color')

			->li(form::upload('transactions', 'Import v2 Transactions', 'avia-color-theme-color', ['style' => 'width:150px', 'hidden' => true, 'onchange' => 'this.form.submit()']))

			->li(form::upload('product', 'Update NDCs', 'avia-color-theme-color', ['style' => 'width:150px', 'hidden' => true, 'onchange' => 'this.form.submit()']).'Requires '.html::link('http://www.fda.gov/downloads/Drugs/DevelopmentApprovalProcess/UCM070838.zip', "product.txt"))

			->li(form::submit('Update Imprints', '', ['style' => 'width:150px']))

			->li(form::upload('nadac', 'Update Prices', 'avia-color-theme-color', ['style' => 'width:150px', 'hidden' => true, 'onchange' => 'this.form.submit()']).'Requires '.html::link('http://www.medicaid.gov/Medicaid-CHIP-Program-Information/By-Topics/Benefits/Prescription-Drugs/Downloads/NADAC/'.date('Y-F').'-NADAC-Files.zip', "nadac.csv"))

			->li(form::submit('Update Images', '', ['style' => 'width:150px']))

		->end();
