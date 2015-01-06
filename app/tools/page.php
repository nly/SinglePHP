<?php
/**
 * Created by PhpStorm
 * @desc: 翻页相关
 * @package: Tools
 * @author: leandre
 * @copyright: copyright(2014)
 * @version: 14/12/22
 */

namespace Tools;

use Lib\Util\Context;

/**
 * Class Page
 * @package Tools
 */
class Page
{
    /**
     * @var int 总条目数量
     */
    private $totalnum;
    /**
     * @var int 每页条目数
     */
    private $pageRecNum;
    /**
     * @var int 当前页码数
     */
    private $pagenum;
    /**
     * @var string url
     */
    private $url;
    /**
     * @var array page数据
     */
    private $pageDate;
    private $bp;

    /**
     * @param $pageRecNum int 每页条目数
     * @param $pagenum int 当前页码数
     * @param $url string 页码前面的url
     * @param $totalnum int 总条目数量
     * @param string $bp 输出数组
     */
    public function __construct($pageRecNum, $pagenum, $url, $totalnum, $bp = "0")
    {
        $this->pageRecNum = $pageRecNum;
        $this->pagenum = $pagenum;
        $this->url = $url;
        $this->bp = $bp;
        if (substr($this->url, 0, 1) == '?') {
            $this->url = 'http://' . Context::get_server('HTTP_HOST') . Context::get_server('REQUEST_URI') . $this->url;
        }
        if ($totalnum === '') {
            $totalnum = 0;
        }
        $this->totalnum = $totalnum;
    }

    /**
     * page数据
     * @return mixed
     */
    public function getPageData()
    {
        $page_count = 1;
        if ($this->totalnum) {
            if ($this->totalnum < $this->pageRecNum) {
                $page_count = 1;
            } else
                if ($this->totalnum % $this->pageRecNum) {
                    $page_count = (int)($this->totalnum / $this->pageRecNum) + 1;
                } else {
                    $page_count = $this->totalnum / $this->pageRecNum;
                }
        }
        if ($this->pagenum <= 1) {
            $this->pagenum = 1;
            $this->pageDate['firstpage'] = Context::get_server('REQUEST_URI') . '#';
            $this->pageDate['previouspage'] = Context::get_server('REQUEST_URI') . '#';
        } else {
            $this->pageDate['firstpage'] = $this->url . '1';
            $this->pageDate['previouspage'] = $this->url . ($this->pagenum - 1);
        }
        if (($this->pagenum >= $page_count) || ($page_count == 0)) {
            $this->pagenum = $page_count;
            $this->pageDate['nextpage'] = Context::get_server('REQUEST_URI') . '#';
            $this->pageDate['lastpage'] = Context::get_server('REQUEST_URI') . '#';
        } else {
            $this->pageDate['nextpage'] = $this->url . ($this->pagenum + 1);
            $this->pageDate['lastpage'] = $this->url . $page_count;
        }
        $this->pageDate['totalpage'] = $page_count;
        $this->pageDate['pagenum'] = $this->pagenum;

        $this->pageDate['from'] = ($this->pagenum - 1) * $this->pageRecNum + 1;
        if ($this->totalnum == 0) {
            $this->pageDate['from'] = 0;
        }
        if ($this->pagenum * $this->pageRecNum > $this->totalnum) {
            $this->pageDate['to'] = $this->totalnum;
        } else {
            $this->pageDate['to'] = ($this->pagenum) * $this->pageRecNum;
        }
        $this->pageDate['totalnum'] = $this->totalnum;
        $this->pageDate['pageRecNum'] = $this->pageRecNum;
        $this->pageDate['pageurl'] = $this->url;
        $this->pageDate['bp'] = $this->bp;
        return $this->pageDate;
    }

    /**
     * 列表式分页
     * @param int $listnum
     * @param string $omimark
     * @return array
     */
    public function getpagelist($listnum = 7, $omimark = "...")
    {
        $this->getPageData();
        $pagelist = array();
        $begin = $last = array();

        $rim_num = floor($listnum / 2) + 1;

        if (($this->pagenum > $rim_num && $this->pageDate['totalpage'] > $listnum) && ($this->pageDate['totalpage'] - $this->pagenum > $rim_num)) // 两头的...都存在时
        {
            $begin[] = array("num" => 1, "url" => $this->url . "1");
            $begin[] = array("num" => $omimark, "url" => "");
            $last[] = array("num" => $omimark, "url" => "");
            $last[] = array("num" => $this->pageDate['totalpage'], "url" => $this->url . $this->pageDate['totalpage']);

            $firstpage = $this->pagenum - $rim_num + 2;
            $endpage = $this->pagenum + $rim_num - 2;
        } elseif ($this->pagenum > $rim_num && $this->pageDate['totalpage'] > $listnum) // 只有开头的...时
        {
            $begin[] = array("num" => 1, "url" => $this->url . "1");
            $begin[] = array("num" => $omimark, "url" => "");

            $firstpage = $this->pageDate['totalpage'] - $listnum + 2;
            $endpage = $this->pageDate['totalpage'];
        } elseif ($this->pageDate['totalpage'] - $this->pagenum > $rim_num && $this->pageDate['totalpage'] > $listnum) // 只有结尾的...时
        {
            $last[] = array("num" => $omimark, "url" => "");
            $last[] = array("num" => $this->pageDate['totalpage'], "url" => $this->url . $this->pageDate['totalpage']);

            $firstpage = 1;
            $endpage = $listnum - 1;
        } else // 没有...时
        {
            $firstpage = 1;
            $endpage = $this->pageDate['totalpage'];
        }

        for ($i = $firstpage; $i <= $endpage; $i++) {
            $pagelist[$i]['num'] = $i;
            $pagelist[$i]['url'] = $this->url . $i;
        }
        $pagelist = array_merge($begin, $pagelist, $last);
        return $pagelist;
    }
}