# cloud_infra2020
Web API /var/www/html/vmctl  
SSH Key /var/www/sshkeys  
SQLite3ファイル /var/lib/phpliteadmin/vmctl.db  
SQLiteの仕様で，時刻はすべてUTC
テスト作業用 動作に影響せず ./test  

# 最低限の実装について

- VMの起動と終了をコマンドラインで作成してください。  
virshコマンドを利用  
起動 ./vmctl/seeddata/virt-install.php  
終了 ./vmctl/bootdelete.php (強制終了)  
通常終了はゲストOSからの操作を想定し，API経由は省略しました．  
- 作成したコマンドラインをWebAPIから呼び出せるようにして、curlコマンド経由でVMの起動と終了ができるようにしてください。  
一通りできます．詳細はAPIリファレンス参照．  
- Databaseに、ホストマシンのCPUやメモリ、HDD容量と言ったキャパシティを記録し、WebAPI内部で管理してください。  
serverテーブル (キャパシティ)  
vmテーブル (VMごとのリソース消費)  
- VM起動時にホストマシンのキャパシティを考慮し、VMを起動する場所を選択するようにしてください。(スケジューラの実装)  
vmpost.php 60行目付近 メモリのみに着目し，最も消費が少ない物理サーバ番号を返す  
- 実際に起動する場所が決まったら、そのVMで利用されるキャパシティの一部を減らし、キャパシティを超えたVMの起動をしないようにチェックする部分も実装してください。  
チェックvmpost.php 100行目付近，利用するリソースをDBに書き込みvmpost.php 170行目付近
- 起動したVMの情報もDatabaseで管理してください。  
vmテーブル  
- VMの終了は、VMを実際に削除し、その分のキャパシティを戻すようにDatabaseを更新してください。  
vmdelete.php 39行目, 56行目付近  
- VMのマシンイメージに、ブート時にSSH設定をするスクリプトを書いてください。  
./seeddata/centos7/meta-data.php  
./seeddata/centos8/meta-data.php  
./seeddata/ubuntu2004/meta-data.php  
- 上記スクリプトを組み込んだマシンイメージを原本にして、VM起動するようにしてください。  
元となるマシンイメージは/var/kvm/masterに配置．CentOS7, CentOS8, Ubuntu20.04の3つ  
vmpost.php 160行目付近でこのイメージを原本としてqemu-img createでゲストVMのイメージを作成  
- SSH設定するスクリプトは、メタデータドライブの内容を参照して、公開鍵を設定する処理をします。  
virt-install.phpのオプションでメタデータドライブを指定．メタデータドライブの作成はvmpost.phpの139行目付近  
- WebAPIで、鍵管理をするAPIを作ってください。鍵を作成、削除するAPIです。  
ユーザーとパスワード，公開鍵は1:1で対応  
keyget.php 公開鍵表示  
keypost.php 新ユーザー作成，公開鍵の指定がないときは鍵ペア生成  
keyput.php 公開鍵更新  
keydelete.php ユーザーと公開鍵を削除  
- VM起動時に、ここで作成した鍵を指定できるようにしてください。  
DBから公開鍵を読み出し，meta-data.phpに値を埋め込んで指定  
- VM起動のコマンドラインを変更し、指定された公開鍵をメタデータドライブに書き込むように作ってください。これで、VMがブートすると、SSH設定するスクリプトがその鍵を読み込み、秘密鍵で外部からSSH接続できるようになります。  
meta-data.phpでuser-dataとして書き込む仕様  

# 評価について
## 機能面
- 上記機能が実装されているか
- それによってオートスケール可能な要件を満たしているか
- WebAPIは機能しているか
- VMの起動、SSH接続、終了ができているか
- VMのステータス管理はされているか
- 物理サーバ上にVMは分散して起動するか
VMのステータス管理(pending, running, shut offなど)はできていない．defined or notのみ管理．  
undefineと同時にディスクも削除する仕様．  
複数台一括して起動できる機能では，すべてのリソースに重複がないことを確認し，DB上で予約してから物理サーバに分散して起動している．  

## リソースマネジメント
- キャパシティの考え方が実装されているか  
DBを利用  
- WebAPIを通じてユーザが望む任意のVMが作成できるようになっているか(2パターン以上あれば良い)
物理サーバ, メモリ，ディスク，CPU数, OSが可変  

## コード
- 設計に工夫はあるか(これはDataCenterManagerなど機能オブジェクトが設計されていれば十分です)  
- キャパシティオーバー時などのエラーハンドリングがあるか  
- 読みやすいか  
SSH接続やDB接続などは関数や外部ファイルとして記述  
エラー処理は可能な限り記述し，どこで失敗しているか特定しやすくした．エラーコードはあまり意味がない．  
読み直しのためコメントを記述  

## 特別加点
- GUIがある場合  
一応あります  
- アプローチが変わっている場合  
公式配布のクラウド用イメージを利用し，cloud-initを利用  
qcow2により元イメージからの差分のみで起動が高速でバルーニングできる  
- 特筆すべき機能がある場合  
一度のコマンドで，分散して複数台のVMを起動できるので，ちゃんと実装すればオートスケールを実現できる  
- プレゼンテーションに工夫がみられる場合  
時間が限られているので，構成図からスタートして，デモを中心にします．

## その他
- 授業への参加(出席など)  
各回参加しました．  
