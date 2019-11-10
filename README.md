# munch
toy project 

php codeigniter 로 만들었습니다.

codeigniter 에서 자체 orm 만들어보려고 했습니다.
만든이유 -> 컨트롤러 에서 함수 네이밍만 보고 어떤 역할을 하는지 알 수 있도록 만들었습니다.
추후에 CI_MODEL 에 하단에 있는 함수를 작업할 예정이고, model 에서 CI_MODEL 상속 받을 수 있도록 추가 작업할 예정입니다.

/app/models/~~.php

setWhere,
doRegister
doUpdate,

<< model 에서 사용하는 함수 >>
doRegister -> 등록하는 함수 <br/>
doUpdate -> 수정하는 함수 <br/>
setWhere -> 데이터를 조회해오거나, 리스트를 가지고오거나, 등등 DB 에서 where 역할을 하는 함수 <br/>
getList -> list 에 limit && offset 등 넣으면 페이징되어 리스트 가지고오는 함수 <br/>
getData -> 하나의 데이터를 조회할때 사용하는 함수 <br/>

추후 더 업데이트 할 예정 있습니다..! <br/>
