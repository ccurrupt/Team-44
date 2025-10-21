public class Test{
    public static void main (String[] args){
        test();
    }

    static void test(){
        Math mObj = new Math();
        int result = mObj.add(2,4)
        if (result==6)
            System.out.print("y")
        else
            System.out.print("n")
    }
}