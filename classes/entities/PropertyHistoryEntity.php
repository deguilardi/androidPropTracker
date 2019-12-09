<?php
class PropertyHistoryEntity{
    public $commit;
    public $oldValue;
    public $newValue;

    public function __construct( $commit, $oldValue, $newValue ){
        $this->commit = $commit;
        $this->oldValue = $oldValue;
        $this->newValue = $newValue;
    }

    public function getFormatedDate( $granularity ){
    	$year = $this->commit->date[ "year" ];
        $month = $this->commit->date[ "month" ];

        switch( $granularity ){
            case "quartely":
                $quarter = ceil( $month / 3 );
                $key = $year . "-q" . $quarter;
                break;
            case "monthly":
                $paddedMonth = str_pad( $month, 2, '0', STR_PAD_LEFT );
                $key = $year . "-" . $paddedMonth;
                break;
        }

        return $key;
    }
}