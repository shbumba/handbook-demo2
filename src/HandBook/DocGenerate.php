<?php
namespace HandBook;

use DocxMerge\DocxMerge as DocxMerge;
use PhpOffice\PhpWord\Settings as Settings;

class DocGenerate
{
    public static $tmpDir = './tmp/';
    public static $resultDir = './result/';
    public static $templateDir = './templates/';
    private $book = null;
    private $resultToTemplate = array();
    private $resultToTemplateHand = array();

    public function __construct(Book $book)
    {
        $this->book = $book;
        $this->init();
        $this->prepareResult();
    }

    public function init()
    {
        Settings::loadConfig();

        if (false === tempnam(Settings::getTempDir(), 'DocGenerate')) {
            $this->clearDir();
            Settings::setTempDir(self::$tmpDir);
        } else {
            self::$tmpDir = null;
        }
    }

    public function clearDir()
    {
        if (null === self::$tmpDir) return false;

        if (!is_dir(self::$tmpDir)) {
            mkdir(self::$tmpDir, 0755);
        } else {
            $files = glob(self::$tmpDir . '*');

            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }

        return true;
    }

    public function prepareResult()
    {
        if ($this->book instanceof Book) {
            $timeTo = mktime(0, 0, 0, 1, 1, 2017);
            $iNum = 0;

            foreach ($this->book->getResult() as $notebook) {
                if (!($notebook instanceof NoteBook)) continue;

                foreach ($notebook->getSheets() as $sheet) {
                    if (!($sheet instanceof Sheet)) continue;

                    foreach ($sheet->getHands() as $hand => $page) {
                        if (!($page instanceof Page)) continue;

                        if (!isset($this->resultToTemplateHand[$hand])) {
                            $this->resultToTemplateHand[$hand] = array();
                        }

                        if (!isset($this->resultToTemplate[$iNum])) {
                            $this->resultToTemplate[$iNum] = array();
                        }

                        $dayToFirst = (int)(($page->get('first') - 1) * 24 * 60 * 60);
                        $dayToLast = (int)(($page->get('last') - 1) * 24 * 60 * 60);

                        $currentResult = array(
                            '${firstMonthName}' => strftime('%B', $timeTo + $dayToFirst),
                            '${firstMonthDate}' => strftime('%e', $timeTo + $dayToFirst),
                            '${lastMonthName}' => strftime('%B', $timeTo + $dayToLast),
                            '${lastMonthDate}' => strftime('%e', $timeTo + $dayToLast),
                        );

                        $this->resultToTemplate[$iNum] = $hand;
                        $this->resultToTemplateHand[$hand][$iNum] = $currentResult;
                        $iNum++;
                    }
                }
            }
        }
    }

    public function fileGenerate($data, $prefix)
    {
        $source = self::$templateDir . "Doc-{$prefix}.docx";

        if (!is_file($source)) return false;

        $file = array();

        foreach ($data as $value) {
            $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($source);

            foreach ($value as $key => $val) {
                $templateProcessor->setValue($key, $val);
            }

            $file[] = $templateProcessor->save();
        }

        return $file;
    }

    public function fileCreate($files, $prefix)
    {
        $file = self::$resultDir . 'result-' . $prefix . '.docx';

        $dm = new DocxMerge();
        $dm->merge($files, $file);

        return $file;
    }

    public function createAll()
    {
        $createFile = null;

        $filesAll = array();

        foreach ($this->resultToTemplate as $num => $handKey) {
            if (!isset($this->resultToTemplateHand[$handKey][$num])) continue;

            $file = $this->fileGenerate(array($this->resultToTemplateHand[$handKey][$num]), $handKey);
            $file = isset($file[0]) ? $file[0] : $file;

            $filesAll[] = $file;
        }

        if ($filesAll !== false && count($filesAll) > 0) {
            $createFile = $this->fileCreate($filesAll, 'all');
        }

        $this->clearDir();

        return $createFile;
    }

    public function createByHands()
    {
        $createFiles = array();

        foreach ($this->resultToTemplateHand as $handKey => $handVal) {
            $files = $this->fileGenerate($handVal, $handKey);

            if ($files !== false && count($files) > 0) {
                $createFiles[] = $this->fileCreate($files, $handKey);
            }
        }

        $this->clearDir();

        return $createFiles;
    }
}