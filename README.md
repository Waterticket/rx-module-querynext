QueryNext
============

## 모듈의 목적

- 라이믹스에서 단순 쿼리 생성 작업을 제거함
- JPA와 엔티티를 지원하면서 Java Spring의 이해도가 있다면 xml쿼리 대신 JPA로 쉽게 사용이 가능하도록 함


## 사용예시
```
use Rhymix\Modules\Querynext\Models\DBImpl AS DB; // JPA 기능이 추가된 DB 클래스 (extends Rhymix\Framework\DB)

class ExampleController extends Base
{
    public function getData(): stdClass
    {
        $oDB = DB::getInstance();
		$output = $oDB->executeJPAQuery('hotopay.getHotopayPurchaseByPurchaseSrl', ['purchase_srl' => 1]); // 쿼리 폴더에 없는 쿼리 실행
		return $output->data;
    }
}

```
XML 쿼리를 작성하지 않고, 쿼리명에 원하는 조건문을 적어 실행할 수 있다.

- 쿼리 생성 참고 #1: https://docs.spring.io/spring-data/jpa/reference/jpa/query-methods.html#jpa.query-methods.query-creation


### 비고
포에시스의 "라이믹스/XE 모듈 생성기"를 사용하여 제작한 모듈입니다.

https://www.poesis.dev/
