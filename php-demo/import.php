
<?php

if (!defined('_PS_VERSION_'))
    exit;
# Use Requests API (http://requests.ryanmccue.info/)
# You can use any php library but requests seems easier to handle sessions.

require_once 'Requests/library/Requests.php';
require_once(_PS_ROOT_DIR_ . '/config/config.inc.php');
Requests::register_autoloader();

class ABS {

    public $session;
    public $count;

    public function execute($abs_url, $abs_username, $abs_password, $category_id) {
        try {
            //get all products from ABS
            $this->count = 0;
            $this->session = new Requests_Session($abs_url);
            $this->session->headers['Content-Type'] = 'application/json';
            $this->session->headers['X-Requested-With'] = 'XMLHttpRequest';
            # now login
            $data = array(
                'username' => $abs_username,
                'password' => $abs_password
            );
            $response = $this->session->post('login.jsp', array(), json_encode($data));          
            $response_products = $this->session->get("ws/rest/com.axelor.apps.base.db.Product?limit=-1");
            $product_array = json_decode($response_products->body, TRUE);
            $this->count = 0;
            //loop throughout the products to find the location lines
            foreach ($product_array['data'] as $prod) {
                $datajson = '{"offset": 0,"limit": 20,"data": {"_domain": "self.product.id =:_product","_domainContext": { "_product": ' . $prod['id'] . '}}}';
                $response_stock = $this->session->post('ws/rest/com.axelor.apps.supplychain.db.LocationLine/search', array(), $datajson);
                $result_locations = json_decode($response_stock->body, TRUE);
                if ($result_locations['total'] > 0) {
                    self::checkProduct($prod, $result_locations, $category_id);
                }
            }
            return $this->count . ' product(s) imported.';
        } catch (Exception $e) {
            return 'There was an error while importing. It may be a connection error.';
        }
    }

    public function checkProduct($prod, $result_locations, $category_id) {
        $data = $result_locations['data'];
        $quantity = $this->countQuantity($data);
        $id_product = (int) Db::getInstance()->getValue('SELECT id_product FROM ' . _DB_PREFIX_ . 'product WHERE reference = \'' . pSQL($prod['code']) . '\'');
        if ($id_product != 0) {
            $updated_product = new Product((int) $id_product, TRUE);
            if (!$prod['updatedOn'] == NULL && !(strtotime($updated_product->date_upd) > strtotime($prod['updatedOn']))) {
                $this->updateProduct($id_product, $quantity, $prod);
                $this->count += 1;
                //else if (strtotime($updated_product->date_upd) < strtotime($prod['updatedOn'])) {
//                        //send data to ERP
//                        $putjson = '{"data": {"currentQty": ' . $updated_product->quantity . ' , "id": ' . $jsproduct['id'] . ' }}}';
//                        $request_stock = $session->post('ws/rest/com.axelor.apps.supplychain.db.LocationLine/' . $jsproduct['id'], array(), $putjson);
//                        $req_output = json_decode($request_stock->body);
//                        print_r($req_output);
//                    }
            }
        } else {
            $this->addProduct($quantity, $prod, $category_id);
            $this->count += 1;
        }
    }

    public function countQuantity($result_locations) {
        $qty = 0;
        foreach ($result_locations as $jsproduct) {
            if ($jsproduct['currentQty'] > 0) {
                $qty += $jsproduct['currentQty'];
            }
        }
        return $qty;
    }

    public function addProduct($quantity, $prod, $category_id) {
        $product = new Product();
        $category = new Category((int) $category_id, true);
        $product->reference = $prod['code'];
        $product->active = (int) 1;
        $product->category = array($category_id);
        $product->id_category_default = $category->id;
        $product->date_add = date('Y-m-d H:i:s');
        $this->saveProduct($product, $prod);
        $product->updateCategories($product->category, true);
        self::addQuantity($product->id, $quantity);
        if ($prod['picture']['id'] != NULL) {
            $res1 = $this->session->get("ws/rest/com.axelor.meta.db.MetaFile/" . $prod['picture']['id'] . "/content/download");
//            $binary = $res1->body;
            $name = $prod['picture']['fileName'];
            $this->addImage($product->id, $name, $res1->body);
        }
    }

    public function updateProduct($id_product, $quantity, $product) {
        $prod = new Product((int) $id_product, TRUE);
        $this->saveProduct($prod, $product);
        self::addQuantity($id_product, $quantity);
        $id_image = (int) Db::getInstance()->getValue('SELECT id_image FROM ' . _DB_PREFIX_ . 'image WHERE id_product = \'' . pSQL($id_product) . '\'');
        if ($id_image != NULL) {
            $image = new Image((int) $id_image);
            $image->deleteImage(TRUE);
            $image->delete();
        }
        if ($product['picture']['id'] != NULL) {
            $res1 = $this->session->get("ws/rest/com.axelor.meta.db.MetaFile/" . $product['picture']['id'] . "/content/download");
//            $binary = $res1->body;
            $name = $product['picture']['fileName'];
            /* @var $product Product */
            $this->addImage($prod->id, $name, $res1->body);
        }
    }

    public function saveProduct($product, $prod) {
        $product->price = (float) $prod['salePrice'];
        $product->name[1] = $prod['name'];
        $product->description[1] = utf8_encode($prod['description']); //array((int) (Configuration::get('PS_LANG_DEFAULT')) => $prod['description']);
        $product->description_short[1] = utf8_encode($prod['productTypeSelect']);
        $product->link_rewrite[1] = Tools::link_rewrite($prod['name']);
        $product->date_upd = date('Y-m-d H:i:s');
        $product->save();
    }

    protected function addQuantity($id_product, $quantity) {
        Db::getInstance()->update('stock_available', array(
            'quantity' => (int) $quantity
                ), '`id_product` = ' . (int) $id_product
        );
    }

    public function getImage($id_image) {
        $image = new Image((int) $id_image);
        $path = $image->getImgFolder();
        return $path;
    }

    public function addImage($id_product, $name, $image_context) {
        $image = new Image();
        $image->id_product = $id_product;
        $image->position = Image::getHighestPosition($id_product) + 1;
        $image->cover = true; // or false;
        $image->legend = $name;
        $image->save();
        $path = $image->getPathForCreation();
        if (!self::createImagesofAbs($image_context, $path)) {
            echo 'Error in creating Images.';
        }
    }

    protected function createImagesofAbs($binary, $path) {
        try {
            $filenm = $path . '.jpg';
            $file = fopen($filenm, 'wb');
            fwrite($file, $binary);
            fclose($file);

            ImageManager::resize($filenm, $path . '-home_default.jpg', 124, 124);
            ImageManager::resize($filenm, $path . '-small_default.jpg', 45, 45);
            ImageManager::resize($filenm, $path . '-medium_default.jpg', 58, 58);
            ImageManager::resize($filenm, $path . '-large_default.jpg', 264, 264);
            ImageManager::resize($filenm, $path . '-thickbox_default.jpg', 600, 600);
            return TRUE;
        } catch (Exception $e) {
            return FALSE;
        }
    }

}
?>
