# JK INNOVATION 발주관리 시스템

제조업을 위한 발주·재고·승인 워크플로우를 일원화한 JK INNOVATION 전용 시스템입니다. Cafe24 공유호스팅 환경에서 PHP 8.1, MySQL 8.0 기반으로 동작하도록 설계되었습니다.

## 주요 기능
- 제품 마스터 관리 (복수 공급사 연결, 안전재고 관리)
- 발주서 작성 및 3단계 승인 워크플로우
- 공급업체 평가 및 거래 이력 관리
- 재고 입출고 트래킹 및 안전재고 알림
- KPI 대시보드, 차트, Excel/PDF 내보내기 훅 제공
- JWT 인증 & RBAC (ADMIN/BUYER/APPROVER/VIEWER)

## 기술 스택
- **언어**: PHP 8.1+, Vanilla JavaScript
- **DB**: MySQL 8.0
- **아키텍처**: Clean Architecture, SOLID, MVC
- **인증**: JWT + RBAC
- **스타일**: 칸딘스키 색채 이론 기반 7가지 감정 팔레트

## 설치 방법
1. 저장소 클론 후 웹 루트에 배포합니다.
2. `.env.example`를 복사하여 `.env` 파일을 생성하고 DB 및 JWT 값을 설정합니다.
3. MySQL에 `database_schema.sql`, `triggers_procedures.sql`, `initial_data.sql` 순으로 실행합니다.
4. Cafe24 **웹방화벽 > PHP 버전**을 8.1 이상으로 설정합니다.
5. `public/` 디렉터리를 웹 루트로 지정하거나 Apache `.htaccess` 재작성 규칙을 활성화합니다.

```bash
cp .env.example .env
# database_schema.sql -> triggers_procedures.sql -> initial_data.sql 실행
```

## Cafe24 배포 체크리스트
- `public` 디렉터리를 DocumentRoot로 설정
- `config/database.php`에서 Cafe24 DB 계정 정보로 환경변수 구성
- `php.ini` 커스텀 설정: `session.auto_start=0`, `display_errors=Off`
- `cron` 설정 시 `php -d detect_unicode=0` 옵션 제거

## API 요약
| 메서드 | 경로 | 설명 | 권한 |
|--------|------|------|------|
| POST | /api/login | 로그인 | 공용 |
| GET | /api/profile | 내 정보 | 인증 |
| GET | /api/dashboard | KPI 및 차트 | 인증 |
| GET | /api/products | 제품 목록 | 인증 |
| POST | /api/products | 제품 등록 | ADMIN, BUYER |
| GET | /api/products/{id} | 제품 상세 | 인증 |
| PUT | /api/products/{id} | 제품 수정 | ADMIN, BUYER |
| DELETE | /api/products/{id} | 제품 삭제 | ADMIN |
| GET | /api/suppliers | 공급업체 목록 | 인증 |
| POST | /api/suppliers | 공급업체 등록 | ADMIN, BUYER |
| PUT | /api/suppliers/{id} | 공급업체 수정 | ADMIN, BUYER |
| GET | /api/purchase-orders/generate-number | 발주번호 생성 | ADMIN, BUYER |
| POST | /api/purchase-orders | 발주서 등록 | ADMIN, BUYER |
| GET | /api/purchase-orders/{id} | 발주서 상세 | 인증 |
| GET | /api/inventory/{productId} | 재고 조회 | 인증 |
| POST | /api/inventory/adjust | 재고 조정 | ADMIN, BUYER |
| POST | /api/inventory/receive | 발주 입고 | ADMIN, APPROVER |

## 초기 계정
| 이메일 | 역할 | 비밀번호 |
|--------|------|-----------|
| admin@jkinnovation.com | ADMIN | Admin1234! |
| buyer@jkinnovation.com | BUYER | Admin1234! |
| manager@jkinnovation.com | APPROVER | Admin1234! |
| director@jkinnovation.com | APPROVER | Admin1234! |

## 개발 참고
- 모든 API 응답은 JSON 형식을 따릅니다.
- 입력 검증 실패 시 422 코드와 에러 메시지를 반환합니다.
- JWT 토큰은 `Authorization: Bearer <token>` 헤더에 포함합니다.
- Cafe24의 `mod_security`와 충돌하지 않도록 PUT/DELETE 요청은 `fetch` 사용 시 `X-HTTP-Method-Override` 옵션 없이 전송합니다.

## 라이선스
사내 전용 프로젝트로 외부 배포를 금합니다.
