Yii 2 CSV Data Export extension Change Log
==========================================

1.0.3, February 9, 2018
-----------------------

- Bug #13: Fixed result file is not deleted after `ExportResult::send()` at some environment (danielkesselberg, klimov-paul)
- Enh #15: Added ability to use custom export result class (danielkesselberg)


1.0.2, November 6, 2017
-----------------------

- Bug: Usage of deprecated `yii\base\Object` changed to `yii\base\BaseObject` allowing compatibility with PHP 7.2 (klimov-paul)
- Enh #2: Added `SerialColumn` column (klimov-paul)
- Enh #11: Added `CsvFile::$writeBom` allowing to automatically write BOM for generated files (wcoc, klimov-paul)
- Chg #8: Default value of `DataColumn::$format` changed to 'raw' instead of 'text' (klimov-paul)


1.0.1, June 28, 2016
--------------------

- Bug #1: Fixed `CsvGrid::export()` does not create result file from empty data set (klimov-paul)


1.0.0, June 3, 2016
-------------------

- Initial release.
