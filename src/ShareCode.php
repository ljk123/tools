<?php


namespace Tools;


class ShareCode
{


    /**
     * 根据因子打乱字符串顺序
     * @param $factor
     * @return array ['hash','factor']
     */
    private static function hash($factor = '')
    {
        $base_string_len = strlen(self::$base_string);
        if (empty($factor) || strlen($factor) !== self::$factor_len) {
            $factor = '';
            for ($i = 0; $i < self::$factor_len; $i++) {
                $factor .= substr(self::$base_string, mt_rand(0, $base_string_len - 1), 1);
            }
        }

        $exponent = $base_string_len + self::base_decode($factor, self::$base_string);
        $time = $exponent;
        $arr = str_split(self::$base_string);
        $offset = $exponent;
        for ($i = 0; $i < $time; $i++) {
            $offset += $i;
            $offset = $offset % $base_string_len;
            if ($i % 2) {
                array_push($arr, array_splice($arr, $offset, 1)[0]);
            } else {
                array_unshift($arr, array_splice($arr, $offset, 1)[0]);
            }

        }
        return [join('', $arr), $factor];
    }

    private static function base_encode($int, $hash)
    {
        $toValue = '';
        $hash_len = strlen($hash);
        $shang = $int + pow($hash_len, 2);
        do {
            $yushu = $shang % $hash_len;
            $shang = floor($shang / $hash_len);
            $toValue = $hash{$yushu} . $toValue;
        } while ($shang > 0);
        return $toValue;
    }

    private static function base_decode($base30, $hash)
    {
        $str = 0;
        $len = strlen($base30);
        $hash_len = strlen($hash);
        for ($i = 0; $i < $len; $i++) {
            $str += strpos($hash, $base30{($len - $i - 1)}) * pow($hash_len, $i);
        }
        return $str - pow($hash_len, 2);
    }

    /***
     * 加码
     * @param int $user_id
     * @param string $error
     * @param bool $is_fixed 是否固定因子
     * @return string
     */
    public function encode($user_id, &$error = '', $is_fixed = false)
    {
        if (!is_numeric($user_id) || $user_id < 0) {
            $error = '`user_id` must be a numeric and bigger than zero';
            return false;
        }
        $factor = '';
        if ($is_fixed) {
            for ($i = 0; $i < self::$factor_len; $i++) {
                $factor .= self::$base_string{pow($user_id + 10, $i) % strlen(self::$base_string)};
            }
        }
        list($hash, $factor) = self::hash($factor);
        $toValue = self::base_encode($user_id, $hash);
        return $factor . $toValue;

    }


    /**
     * 解码
     * @param string $code
     * @return int
     */
    public function decode($code)
    {
        //第一位是因子
        $factor = substr($code, 0, self::$factor_len);
        $hash = self::hash($factor)[0];
        //去掉第一位
        $code = substr($code, self::$factor_len);

        return self::base_decode($code, $hash);

    }

    private static $instance = null;

    /**
     * 去掉 0O 1I 2Z
     * @var string
     */
    private static $base_string = '3456789ABCDEFGHJKLMNPQRSTUVWXY';
    private static $factor_len = 2;//因子长度 越大离散数越高  离散数=pow(30,len)


    private function __construct($cfg)
    {
        if (!empty($cfg['factor_len']) && (int)$cfg['factor_len'] > 0) {
            self::$factor_len = (int)$cfg['factor_len'];
        }
    }

    public static function getInstance($cfg = [])
    {
        if (null === self::$instance) {
            self::$instance = new self($cfg);
        }
        return self::$instance;
    }

    /**
     * 设置因子长度
     * @param $len
     * @return int
     */
    public static function setFactorLen($len)
    {
        return self::$factor_len = (int)$len;
    }

    /**
     * 获取因子长度
     * @return int
     */
    public static function getFactorLen()
    {
        return self::$factor_len;
    }

    /**
     * 静态加码
     * @param $user_id
     * @param string $error
     * @param bool $is_fixed 是否固定因子
     * @return string
     */
    public static function getEncode($user_id, &$error = '', $is_fixed = false)
    {
        return self::getInstance()->encode($user_id, $error, $is_fixed);
    }

    /**
     * 静态解码
     * @param $code
     * @return int
     */
    public static function getDecode($code)
    {
        return self::getInstance()->decode($code);
    }
}