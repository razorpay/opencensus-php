import  com.infosys.feba.tools.shoppingmallencryption.ShoppingMallSymmetricCipherHelper;
import java.net.URLEncoder;
import java.security.*;

public class Oriental {
	public static void main(String[] args) throws Exception {
		String encryptedVal=null;
		String QS = "ABCDEF";
		encryptedVal=ShoppingMallSymmetricCipherHelper.encrypt(QS, "ABCDEFGHIJKLMNOP", "AES");
		System.out.println(encryptedVal);
		String d = "";
		try {
			d = ShoppingMallSymmetricCipherHelper.decrypt(encryptedVal, "ABCDEFGHIJKLMNOP", "AES");
		}
		catch(Exception e) {
			System.out.println("failed");
			System.out.println(e.getMessage());
		}

		System.out.println(d);
	}
}


// jhcOCdwG5APgOmoOuq/SQ==
// ABCDEF
