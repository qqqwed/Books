<?php
    /** 地理编码功能 */
    trait Geocodable {

    /** @var string 地址 */
    protected $address;
    
    /** @var \Geocoder\Geocoder 地理编码器对象 */
    protected $geocoder;

    /** @var \Geocoder\Result\Geocoded 地理编码器处理得到的结果对象 */
    protected $geocoderResult;

    /**
     * 注入Geocoder对象
     *
     * @param \Geocoder\GeocoderInterface $geocoder
     * @return void
     */
    public function setGeocoder(\Geocoder\GeocoderInterface $geocoder)
    {
        $this->geocoder = $geocoder;
    }
    
    /**
     * 设定地址
     *
     * @param [string] $address 地址
     * @return void
     */
    public function setAddress($address)
    {
        $this->address = $address;
    }

    /**
     * 获得纬度
     *
     * @return string
     */
    public function getLatitude()
    {
        if (!isset($this->geocoderResult)) {
            $this->geocoderAddress();
        }

        return $this->geocoderResult->getLatitude();
    }

    /**
     * 获取经度
     *
     * @return string
     */
    public function getLongtitude()
    {
        if (!isset($this->geocoderResult)) {
            $this->geocodeAddress();
        }

        return $this->geocoderResult->getLongtitude();
    }
    
    /**
     * 把地址传给地理编码实例,获取地理编码处理得到的结果
     *
     * @return boolean
     */
    protected function geocodeAddress()
    {
        $this->geocoderResult = $this->geocoder->geocode($this->address);

        return true;
    }
}